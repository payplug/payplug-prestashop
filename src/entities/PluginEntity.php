<?php


namespace PayPlug\src\entities;



class PluginEntity
{
    private $api_version;
    private $logger;

    /**
     * @return mixed
     */
    public function getApiVersion()
    {
        return $this->api_version;
    }

    /**
     * @param mixed $api_version
     * @return PluginEntity
     */
    public function setApiVersion($api_version)
    {
        $this->api_version = $api_version;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param mixed $logger
     * @return PluginEntity
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

}