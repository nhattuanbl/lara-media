<?php

namespace Nhattuanbl\LaraMedia\Enums;

enum PositionEnum: string
{
    case Center = 'main_w/2-overlay_w/2:main_h/2-overlay_h/2';
    case TopLeft = '10:10';
    case TopRight = 'main_w-overlay_w-10:10';
    case BottomLeft = '10:main_h-overlay_h-10';
    case BottomRight = 'main_w-overlay_w-10:main_h-overlay_h-10';



}
