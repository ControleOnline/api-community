<?php

namespace App\Entity;


final class GeoPlace
{

    /**
     * @var string
     */
    public $description;
    /**
     * @var string
     */
    public $country;
    /**
     * @var string
     */
    public $state;
    /**
     * @var string
     */
    public $city;
    /**
     * @var string
     */
    public $district;
    /**
     * @var string
     */
    public $street;
    /**
     * @var string
     */
    public $number;
    /**
     * @var string
     */
    public $postal_code;
    /**
     * @var string
     */
    public $provider;
    /**
     * @var double
     */
    public $lat;
    /**
     * @var double
     */
    public $lng;
}
