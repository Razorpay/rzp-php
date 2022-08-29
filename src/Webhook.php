<?php

namespace Razorpay\Api;


class Webhook extends Entity
{
    /**
     * @param $id webhook id description
     */
    public function create($attributes = array())
    {
        return parent::create($attributes);
    }

    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all($options);
    }

    /**
     * Patches given webhook with new attributes
     *
     * @param array $attributes
     *
     * @return Webhook
     */
    public function edit($id, array $attributes = array())
    {
        $url = $this->getEntityUrl() . $id;

        return $this->request('PUT', $url, $attributes);
    }
}
