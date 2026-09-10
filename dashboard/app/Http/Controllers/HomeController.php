<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Redis;
use RedisException;

class HomeController extends Controller
{
    private Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @throws RedisException
     */
    public function index(): View
    {
        $color = $this->redis->get(SettingsController::WS_SETTINGS_COLOR);
        $logoImage = $this->redis->get(SettingsController::WS_SETTINGS_LOGO_IMAGE);

        return view('home', [
            'color' => $color,
            'logo_image' => $logoImage,
        ]);
    }

    public function form(string $hash): View
    {
        return view('form', [
            'formHash' => $hash,
        ]);
    }
}
