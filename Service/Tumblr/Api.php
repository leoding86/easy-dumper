<?php
namespace Service\Tumblr;

use Common\DumperException;

class Api
{
    const API_BASE     = 'https://api.tumblr.com/';
    const AUTH_NONE    = 0;
    const AUTH_APIKEY  = 1;
    const AUTH_OAUTH   = 2;
    const REQUEST_GET  = 1;
    const REQUEST_POST = 2;

    private $apiKey    = null;
    private $apiSecret = null;
    private $apiUrl    = null;
    private $params    = [];
    private $options   = [];
    private $headers   = [];
    private $result    = null;
    private $error     = null;

    public function __construct($api_key, $api_secret = null)
    {
        $this->apiKey    = $api_key;
        $this->apiSecret = $api_secret;
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public static function __getStatic($property)
    {
        return $this::$property;
    }

    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    public function addOptions($options)
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function removeOptions($options)
    {
        foreach ($this->options as $key => $option) {
            if (in_array($key, $options)) {
                unset($this->options[$key]);
            }
        }
        return $this;
    }

    public function request($api, $request_type, $auth_type)
    {
        try {
            if ($auth_type === self::AUTH_NONE) {
                $params = $this->params;
            } else if ($auth_type === self::AUTH_APIKEY) {
                $params = array_merge(['api_key' => $this->apiKey], $this->params);
            } else if ($auth_type === self::AUTH_OAUTH) {
                // not implements
            }

            if ($request_type === self::REQUEST_GET) {
                $api .= '?' . http_build_query($params);
                $request = \Requests::get($api, $this->headers, $this->options);
            } else if ($request_type === self::REQUEST_POST) {
                $request = \Requests::post($api, $params, $this->headers, $this->options);
            }

            $result = json_decode($request->body);

            if ($result === null) {
                throw new ApiException('Unkown response');
            } else {
                if ((int)$result->meta->status !== 200) {
                    throw new ApiException($result->meta->msg);
                } else {
                    return $result->response;
                }
            }
        } catch (\Requests_Exception $e) {
            throw new ApiException($e->getMessage());
        } catch (\Exception $e) {
            throw new ApiException($e->getMessage());
        }
    }

    public function info($blog_identifier)
    {
        $api = sprintf(self::API_BASE . 'v2/blog/%s/info', $blog_identifier);
        return $this->request($api, self::REQUEST_GET, self::AUTH_APIKEY);
    }

    public function posts($blog_identifier, $type = null)
    {
        $api = sprintf(self::API_BASE . 'v2/blog/%s/posts/%s', $blog_identifier, $type);
        return $this->request($api, self::REQUEST_GET, self::AUTH_APIKEY);
    }
}

class ApiException extends DumperException
{

}