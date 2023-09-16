<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Pricing\V2;

use Twilio\Exceptions\TwilioException;
use Twilio\InstanceContext;
use Twilio\ListResource;
use Twilio\Rest\Pricing\V2\Voice\CountryContext;
use Twilio\Rest\Pricing\V2\Voice\CountryList;
use Twilio\Rest\Pricing\V2\Voice\NumberContext;
use Twilio\Rest\Pricing\V2\Voice\NumberList;
use Twilio\Version;

/**
 * @property CountryList countries
 * @property NumberList numbers
 * @method CountryContext countries(string $isoCountry)
 * @method NumberContext numbers(string $destinationNumber)
 */
class VoiceList extends ListResource {
    protected $_countries = null;
    protected $_numbers = null;

    /**
     * Construct the VoiceList
     * 
     * @param Version $version Version that contains the resource
     * @return VoiceList
     */
    public function __construct(Version $version) {
        parent::__construct($version);

        // Path Solution
        $this->solution = array();
    }

    /**
     * Magic getter to lazy load subresources
     *
     * @param string $name Subresource to return
     *
     * @return ListResource The requested subresource
     * @throws TwilioException For unknown subresources
     */
    public function __get($name) {
        if (property_exists($this, '_' . $name)) {
            $method = 'get' . ucfirst($name);
            return $this->$method();
        }

        throw new TwilioException('Unknown subresource ' . $name);
    }

    /**
     * Magic caller to get resource contexts
     *
     * @param string $name Resource to return
     * @param array $arguments Context parameters
     *
     * @return InstanceContext The requested resource context
     * @throws TwilioException For unknown resource
     */
    public function __call($name, $arguments) {
        $property = $this->$name;
        if (method_exists($property, 'getContext')) {
            return call_user_func_array(array($property, 'getContext'), $arguments);
        }

        throw new TwilioException('Resource does not have a context');
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString() {
        return '[Twilio.Pricing.V2.VoiceList]';
    }

    /**
     * Access the countries
     */
    protected function getCountries() {
        if (!$this->_countries) {
            $this->_countries = new CountryList($this->version);
        }

        return $this->_countries;
    }

    /**
     * Access the numbers
     */
    protected function getNumbers() {
        if (!$this->_numbers) {
            $this->_numbers = new NumberList($this->version);
        }

        return $this->_numbers;
    }
}