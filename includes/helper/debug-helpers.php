<?php

function barr($array) {

    // Start with a styled <pre> container
    $output  = "<pre style='
        background:#1e1e1e;
        color:#dcdcdc;
        padding:15px;
        border-radius:8px;
        font-size:14px;
        overflow:auto;
        line-height:1.5;
        position: relative;
        z-index: 1000;
    '>";

    // Convert array to readable JSON string
    $output .= htmlspecialchars(json_encode(
        $array,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ));

    $output .= "</pre>";

    return $output;
}