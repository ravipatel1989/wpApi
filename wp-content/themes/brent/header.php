<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link       https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package    WordPress
 * @subpackage Twenty_Nineteen
 * @since      1.0.0
 */
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <base href="/" />
    <meta http-equiv=X-UA-Compatible content="IE=edge">
    <meta name=viewport content="width=device-width,initial-scale=1">
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel=icon href=<?php echo get_template_directory_uri() ?>/favicon.ico>
    <link rel="apple-touch-icon" sizes="57x57" href="<?php echo get_template_directory_uri() ?>/img/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="<?php echo get_template_directory_uri() ?>/img/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo get_template_directory_uri() ?>/img/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo get_template_directory_uri() ?>/img/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo get_template_directory_uri() ?>/img/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="<?php echo get_template_directory_uri() ?>/img/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo get_template_directory_uri() ?>/img/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo get_template_directory_uri() ?>/img/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo get_template_directory_uri() ?>/img/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="<?php echo get_template_directory_uri() ?>/img/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo get_template_directory_uri() ?>/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo get_template_directory_uri() ?>/img/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo get_template_directory_uri() ?>/img/favicon-16x16.png">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?php echo get_template_directory_uri() ?>/img/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <link href="<?php echo get_template_directory_uri() ?>/css/app.css" rel="preload" as="style">
    <link href="<?php echo get_template_directory_uri() ?>/css/chunk-vendors.css" rel="preload" as="style">
    <link href="<?php echo get_template_directory_uri() ?>/js/app.js" rel="modulepreload" as="script">
    <link href="<?php echo get_template_directory_uri() ?>/js/chunk-vendors.js" rel="modulepreload" as="script">
    <link href="<?php echo get_template_directory_uri() ?>/css/chunk-vendors.css" rel="stylesheet">
    <link href="<?php echo get_template_directory_uri() ?>/css/app.css" rel="stylesheet">

    <link rel="manifest" href="manifest.json?v=00001">
    <meta name="theme-color" content="#69B46B">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Pathway App">
    <link rel="apple-touch-icon" href="<?php echo get_template_directory_uri() ?>/img/icons/apple-touch-icon-152x152.png?v=00001">
    <link rel="mask-icon" href="<?php echo get_template_directory_uri() ?>/img/icons/safari-pinned-tab.svg?v=00001" color="#69B46B">
    <meta name="msapplication-TileImage" content="<?php echo get_template_directory_uri() ?>/img/icons/msapplication-icon-144x144.png?v=00001">
    <meta name="msapplication-TileColor" content="#000000">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo get_template_directory_uri() ?>/img/icons/favicon-32x32.png?v=00001">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo get_template_directory_uri() ?>/img/icons/favicon-16x16.png?v=00001">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&display=swap" rel="stylesheet">
	<?php wp_head(); ?>
</head>
<body>
<noscript><strong>We're sorry but the site doesn't work properly without JavaScript enabled. Please enable it to continue.</strong></noscript>
<div id=app></div>
<script type="module" src="<?php echo get_template_directory_uri() ?>/js/chunk-vendors.js"></script>
<script type="module" src="<?php echo get_template_directory_uri() ?>/js/app.js"></script>
<script>!function () {
        var e = document, t = e.createElement("script");
        if (!("noModule" in t) && "onbeforeload" in t) {
            var n = !1;
            e.addEventListener("beforeload", function (e) {
                if (e.target === t) n = !0; else if (!e.target.hasAttribute("nomodule") || !n) return;
                e.preventDefault()
            }, !0), t.type = "module", t.src = ".", e.head.appendChild(t), t.remove()
        }
    }();</script>
