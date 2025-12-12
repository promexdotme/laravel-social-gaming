<?php

use Illuminate\Support\Str;						   
if (! function_exists('settings')) {
    /**
     * Get / set the specified settings value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function settings($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('anlutro\LaravelSettings\SettingStore');
        }

        $value = app('anlutro\LaravelSettings\SettingStore')->get($key, $default);
        if ($key === 'frontend' && (!$value || strtolower($value) === 'default')) {
            return 'Minimal';
        }

        return $value;
    }
}

function encoded($str)
{
    return base64_encode(base64_encode($str));
}
function decoded($str)
{
    return base64_decode(base64_decode($str));
}

function hpRand($digit = 4)
{
    return substr(rand(0, 12345) . strrev(time()), 0, $digit);
}
function hpRandStr($digit = 4)
{
    $random = Str::random($digit);
    return $random;
}
