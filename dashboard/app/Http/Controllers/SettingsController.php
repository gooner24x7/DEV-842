<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Redis;
use RedisException;

class SettingsController extends Controller
{
    const string WS_SETTINGS_COLOR = 'ws_settings_color';
    const string WS_SETTINGS_LOGO_IMAGE = 'ws_settings_logo_image';
    private Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @throws RedisException
     */
    public function store(Request $request): JsonResponse
    {
        $color = $request->get('color');
        $logoImage = $request->get('logo_image');

        $this->redis->set(self::WS_SETTINGS_COLOR, $color);
        $this->redis->set(self::WS_SETTINGS_LOGO_IMAGE, $logoImage);

        return new JsonResponse([]);
    }
}
