<?php

namespace Nhattuanbl\LaraMedia\Enums;

enum ResolutionEnum: string
{
    case H240 = '426x240';
    case H360 = '640x360';
    case H480 = '854x480';
    case H720 = '1280x720';
    case H1080 = '1920x1080';
    case H1440 = '2560x1440';
    case H2160 = '3840x2160';
    case H4320 = '7680x4320';

}
