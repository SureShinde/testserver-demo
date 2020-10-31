/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/smart-keyboard-handler',
    'mage/mage',
    'mage/ie-class-fixer',
    'domReady!'
], function ($, keyboardHandler) {
    'use strict';

    if ($('body').hasClass('checkout-cart-index')) {
        if ($('#co-shipping-method-form .fieldset.rates').length > 0 && $('#co-shipping-method-form .fieldset.rates :checked').length === 0) {
            $('#block-shipping').on('collapsiblecreate', function () {
                $('#block-shipping').collapsible('forceActivate');
            });
        }
    }

    $('.cart-summary').mage('sticky', {
        container: '#maincontent'
    });

    $('.panel.header > .header.links').clone().appendTo('#store\\.links');

    $(".main-nav li.ui-menu-item > .open-children-toggle").click(function(){
        if(!$(this).parent().hasClass("opened")) {
            $(this).parent().addClass("opened");
        }
        else {
            $(this).parent().removeClass("opened");
        }
    });

    $('.wraper-main-nav .icon-menu-bar').click(function(){
        if(!$(this).parent().hasClass("active")) {
            $(this).parent().addClass("active");
        }
        else {
            $(this).parent().removeClass("active");
        }
    });

    
    $(document).ready(function($){
        var widthMobile = $(window).width();
        if ( widthMobile < 991 ) {
            $('.side-verticalmenu').removeClass('open');
        }
    });

    keyboardHandler.apply();
});
