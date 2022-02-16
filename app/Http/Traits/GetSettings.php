<?php

namespace App\Http\Traits;

use App\Models\Setting;

trait GetSettings
{
    public function setting(string $param, bool $group = false, bool $all = false)
    {

        if ($all) { //ignore all other params
            $settings = Setting::all();
            return $settings;
        }

        if ($group) { //the $param refers to a group
            $settings = Setting::where('group', $param)->get();
            return $settings;
        }
        
        //param refers to a particular settimg
        $settings = Setting::where('setting', $param)->first();
        return $settings->value;      
    }
}