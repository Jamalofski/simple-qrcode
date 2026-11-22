<?php

namespace SimpleSoftwareIO\QrCode\DataTypes;

/**
 * A Wi-Fi network: phones offer to join it.
 */
class WiFi implements DataTypeInterface
{
    /**
     * The prefix of a Wi-Fi network.
     *
     * @var string
     */
    protected $prefix = 'WIFI:';

    /**
     * The separator between the fields.
     *
     * @var string
     */
    protected $separator = ';';

    /**
     * The encryption of the network, such as WPA or WEP, if any.
     *
     * @var string|null
     */
    protected $encryption;

    /**
     * The SSID, the name of the network.
     *
     * @var string|null
     */
    protected $ssid;

    /**
     * The password of the network, if any.
     *
     * @var string|null
     */
    protected $password;

    /**
     * Whether the network is hidden, if set.
     *
     * @var bool|string|null
     */
    protected $hidden;

    /**
     * Sets the network from its settings.
     *
     * @param array<int, mixed> $arguments An array with encryption, ssid, password and hidden, each one optional
     * @return void
     */
    public function create(array $arguments)
    {
        $this->setProperties($arguments);
    }

    /**
     * Returns the Wi-Fi string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->buildWifiString();
    }

    /**
     * Builds the Wi-Fi string, its fields escaped.
     *
     * @return string
     */
    protected function buildWifiString()
    {
        $wifi = $this->prefix;

        if (isset($this->encryption)) {
            $wifi .= 'T:'.$this->escape($this->encryption).$this->separator;
        }
        if (isset($this->ssid)) {
            $wifi .= 'S:'.$this->escape($this->ssid).$this->separator;
        }
        if (isset($this->password)) {
            $wifi .= 'P:'.$this->escape($this->password).$this->separator;
        }
        if (isset($this->hidden)) {
            // Readers expect true or false: PHP writes the booleans as 1 and an empty string.
            $hidden = is_bool($this->hidden) ? ($this->hidden ? 'true' : 'false') : $this->hidden;
            $wifi .= 'H:'.$hidden.$this->separator;
        }

        return $wifi;
    }

    /**
     * Escapes the characters that delimit the fields of a Wi-Fi string:
     * a backslash before \ ; , " and :, as phone readers expect.
     *
     * @param string $value
     * @return string
     */
    protected function escape($value)
    {
        return addcslashes((string) $value, '\\;,":');
    }

    /**
     * Sets the settings that are given.
     *
     * @param array<int, mixed> $arguments
     * @return void
     */
    protected function setProperties(array $arguments)
    {
        $arguments = $arguments[0];
        if (isset($arguments['encryption'])) {
            $this->encryption = $arguments['encryption'];
        }
        if (isset($arguments['ssid'])) {
            $this->ssid = $arguments['ssid'];
        }
        if (isset($arguments['password'])) {
            $this->password = $arguments['password'];
        }
        if (isset($arguments['hidden'])) {
            $this->hidden = $arguments['hidden'];
        }
    }
}
