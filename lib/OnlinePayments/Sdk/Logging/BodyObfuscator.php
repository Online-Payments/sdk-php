<?php
namespace OnlinePayments\Sdk\Logging;

use UnexpectedValueException;

/**
 * Class BodyObfuscator
 *
 * @package OnlinePayments\Sdk\Logging
 */
class BodyObfuscator
{
    const MIME_APPLICATION_JSON = 'application/json';
    const MIME_APPLICATION_PROBLEM_JSON = 'application/problem+json';

    /** @var  ValueObfuscator */
    protected $valueObfuscator;

    /** @var array<string, callable> */
    private $customRules = array();

    public function __construct()
    {
        $this->valueObfuscator = new ValueObfuscator();
    }

    /**
     * @param string $contentType
     * @param string $body
     * @return string
     */
    public function obfuscateBody($contentType, $body)
    {
        if (!$this->isJsonContentType($contentType)) {
            return $body;
        }
        $decodedJsonBody = json_decode($body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $body;
        }
        return json_encode($this->obfuscateDecodedJsonPart($decodedJsonBody), JSON_PRETTY_PRINT);
    }

    private function isJsonContentType($contentType)
    {
        return $contentType === static::MIME_APPLICATION_JSON
            || $contentType === static::MIME_APPLICATION_PROBLEM_JSON
            || substr($contentType, 0, strlen(static::MIME_APPLICATION_JSON)) === static::MIME_APPLICATION_JSON
            || substr($contentType, 0, strlen(static::MIME_APPLICATION_PROBLEM_JSON)) === static::MIME_APPLICATION_PROBLEM_JSON;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function obfuscateDecodedJsonPart($value)
    {
        if (is_object($value)) {
            foreach ($value as $propertyName => $propertyValue) {
                if (is_scalar($propertyValue)) {
                    $value->$propertyName = $this->obfuscateScalarValue($propertyName, $propertyValue);
                } else {
                    $value->$propertyName = $this->obfuscateDecodedJsonPart($propertyValue);
                }
            }
        }
        if (is_array($value)) {
            foreach ($value as $elementKey => &$elementValue) {
                if (is_scalar($elementValue)) {
                    $elementValue = $this->obfuscateScalarValue($elementKey, $elementValue);
                } else {
                    $elementValue = $this->obfuscateDecodedJsonPart($elementValue);
                }
            }

        }
        return $value;
    }

    /**
     * @param string $key
     * @param scalar $value
     * @return string
     */
    protected function obfuscateScalarValue($key, $value)
    {
        if (!is_scalar($value)) {
            throw new UnexpectedValueException('scalar value expected');
        }
        $lowerKey = mb_strtolower(strval($key), 'UTF-8');
        if (isset($this->customRules[$lowerKey])) {
            return call_user_func($this->customRules[$lowerKey], $value, $this->valueObfuscator);
        }

        switch ($lowerKey) {
            case 'additionalinfo':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'cardholdername':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'dateofbirth':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'emailaddress':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'faxnumber':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'firstname':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'housenumber':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'mobilephonenumber':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'passengername':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'phonenumber':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'street':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'workphonenumber':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'zip':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'keyid':
            case 'secretkey':
            case 'publickey':
            case 'userauthenticationtoken':
            case 'encryptedpayload':
            case 'decryptedpayload':
            case 'encryptedcustomerinput':
                return $this->valueObfuscator->obfuscateFixedLength(8);
            case 'cvv':
            case 'value':
                return $this->valueObfuscator->obfuscateAll($value);
            case 'bin':
                return $this->valueObfuscator->obfuscateAllKeepStart($value, 6);
            case 'accountnumber':
            case 'cardnumber':
            case 'iban':
            case 'reformattedaccountnumber':
                return $this->valueObfuscator->obfuscateAllKeepEnd($value, 4);
            case 'expirydate':
                return $this->valueObfuscator->obfuscateAllKeepEnd($value, 2);
            default:
                return $value;
        }
    }

    /**
     * @param string $propertyName
     * @param callable $customRule
     */
    public function setCustomRule($propertyName, callable $customRule)
    {
        $lowerName = mb_strtolower(strval($propertyName), 'UTF-8');
        $this->customRules[$lowerName] = $customRule;
    }
}
