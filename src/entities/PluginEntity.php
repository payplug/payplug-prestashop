<?php

namespace PayPlug\src\entities;

class PluginEntity
{
    // Vars
    private $api_url;
    private $api_version;

    // Classes
    private $card;
    private $logger;
    private $cache;
    private $query;

    /**
     * @return mixed
     */
    public function getApiUrl()
    {
        return $this->api_url;
    }

    /**
     * @param mixed $api_url
     * @return PluginEntity
     */
    public function setApiUrl($api_url)
    {
        $this->api_url = $api_url;
        return $this;
    }

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
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @param mixed $card
     * @return PluginEntity
     */
    public function setCard($card)
    {
        $this->card = $card;
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

    /**
     * @return mixed
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param mixed $cache
     * @return PluginEntity
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param mixed $query
     * @return PluginEntity
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }
}