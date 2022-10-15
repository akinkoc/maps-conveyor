<?php

  namespace akinkoc\MapsConveyor;

  use Exception;
  use GuzzleHttp\Client;
  use GuzzleHttp\Exception\GuzzleException;

  require 'vendor/autoload.php';

  class MapsConveyor
  {
    private ?Client $http = null;
    private string $API_URL = "https://maps.googleapis.com/maps/api/directions/json";
    private string $destinationLatitude;
    private string $destinationLongitude;
    private string $originLatitude;
    private string $originLongitude;
    private string $key;

    public function __construct()
    {
      if ($this->http == null)
        $this->http = new Client();
    }

    public function setApiKey(string $key): MapsConveyor
    {
      $this->key = $key;
      return $this;
    }

    /**
     * @param string $destinationLatitude
     * @return MapsConveyor
     */
    public function setDestinationLatitude(string $destinationLatitude): MapsConveyor
    {
      $this->destinationLatitude = $destinationLatitude;
      return $this;
    }

    /**
     * @param string $destinationLongitude
     * @return MapsConveyor
     */
    public function setDestinationLongitude(string $destinationLongitude): MapsConveyor
    {
      $this->destinationLongitude = $destinationLongitude;
      return $this;
    }

    /**
     * @param string $originLatitude
     * @return MapsConveyor
     */
    public function setOriginLatitude(string $originLatitude): MapsConveyor
    {
      $this->originLatitude = $originLatitude;
      return $this;
    }

    /**
     * @param string $originLongitude
     * @return MapsConveyor
     */
    public function setOriginLongitude(string $originLongitude): MapsConveyor
    {
      $this->originLongitude = $originLongitude;
      return $this;
    }

    /**
     * @return string
     */
    public function getDestinationLatitude(): string
    {
      return $this->destinationLatitude;
    }

    /**
     * @return string
     */
    public function getDestinationLongitude(): string
    {
      return $this->destinationLongitude;
    }

    /**
     * @return string
     */
    public function getOriginLatitude(): string
    {
      return $this->originLatitude;
    }

    /**
     * @return string
     */
    public function getOriginLongitude(): string
    {
      return $this->originLongitude;
    }

    /**
     * @return array[]|string
     * @throws Exception|GuzzleException
     */
    private function getParsedMapValues(): array|string
    {
      try {
        if (empty($this->getOriginLatitude()) || empty($this->getOriginLongitude())) throw new Exception("Please make sure origin set correctly");
        if (empty($this->getDestinationLatitude()) || empty($this->getDestinationLongitude())) throw new Exception("Please make sure destination set correctly");
        $urlCombined = sprintf("%s?destination=%s,%s&origin=%s,%s&key=%s", $this->API_URL, $this->destinationLatitude, $this->destinationLongitude, $this->originLatitude, $this->originLongitude, $this->key);
        $jsonDecodedRoute = json_decode($this->http->get($urlCombined)->getBody()->getContents());
        $summaries = [];
        $legs = [];
        $steps = [];
        foreach ($jsonDecodedRoute->routes as $route) {
          $summaries[] = $route->summary;
          foreach ($route->legs as $leg) {
            $legs[] = [
              'start_address' => $leg->start_address,
              'end_address' => $leg->end_address,
              'distance' => $leg->distance->text,
              'duration' => $leg->duration->text
            ];
            foreach ($leg->steps as $step) {
              $steps[] = [
                'instruction' => $step->html_instructions
              ];
            }
          }
        }

        return ["summaries" => $summaries, "legs" => $legs, "steps" => $steps];
      } catch (Exception $e) {
        return $e->getMessage();
      }
    }

    public function checkIfInside(string $location): bool
    {
      $location = mb_strtolower($location);
      try {
        $summaries = $this->getParsedMapValues()["summaries"];
        $legs = $this->getParsedMapValues()["legs"];
        $steps = $this->getParsedMapValues()["steps"];

        foreach ($summaries as $summary) {
          if (str_contains(mb_strtolower($summary), $location)) {
            return true;
          }
        }
        foreach ($legs as $leg) {
          if (str_contains(mb_strtolower($leg["start_address"]), $location)) {
            return true;
          }
        }
        foreach ($steps as $step) {
          if (str_contains(mb_strtolower($step["instruction"]), $location)) {
            return true;
          }
        }
      } catch (GuzzleException|Exception) {
        return false;
      }
      return false;
    }
  }