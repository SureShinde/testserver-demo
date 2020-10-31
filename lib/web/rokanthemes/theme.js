/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
define([
    'jquery',
    'rokanthemes/lazyloadimg'
], function ($) {
    'use strict';
	$(window).load(function() {
		$('body').addClass('preloaded');
	});
	$(document).ready(function () {
		
		$(document).on('click', '.grid-mode-show-type-products a', function(){
			$('.grid-mode-show-type-products a').removeClass('actived');
			$(this).addClass('actived');
			var data_view_mode = $('.container-products-switch').attr('data-view-mode');
			var view_mode = $(this).attr('data-grid-mode');
			$('.container-products-switch').removeClass('category_page_grid_'+data_view_mode);
			$('.container-products-switch').attr('data-view-mode',view_mode);
			$('.container-products-switch').addClass('category_page_grid_'+view_mode);
			return false;
		});
		
		if($('#purchase-fake-order').length > 0){
			var show_number_seconds = parseInt($('#purchase-fake-order').attr('data-seconds-displayed'));
			var url_fake = $('#purchase-fake-order').attr('data-url');
			var interval_fake_order = setInterval(getProductRandom, show_number_seconds*1000);
			$(document).on('click', '#purchase-fake-order .purchase-close', function(){
				clearInterval(interval_fake_order);
				$('#purchase-fake-order').removeClass('slideInUp');
				$('#purchase-fake-order').addClass('slideOutDown');
			});
			function getProductRandom(){
				if(!$('#purchase-fake-order').hasClass('slideInUp')){
					$.getJSON(url_fake, function( data ) {
						$('#purchase-fake-order .product-purchase').html(data.html);
						$('#purchase-fake-order').removeClass('slideOutDown');
						$('#purchase-fake-order').addClass('slideInUp');
						$('#purchase-fake-order').removeAttr("style");
					});
				}
				else{
					$('#purchase-fake-order').removeClass('slideInUp');
					$('#purchase-fake-order').addClass('slideOutDown');
				}
			}
		}
		
		$("img.lazy").lazyload({
			skip_invisible: false
		});
		$('.catalog-product-view .reviews-actions a.action').click(function () {
			if($('#tab-label-reviews-title').length){
					$('#tab-label-reviews-title').trigger('click');
			}
			return false;
		});
		$('.header-top-setting a.actions-top').click(function () {
			if($('.header-top-setting .setting-container').hasClass('act-menu-bar')){
				$('.header-top-setting .setting-container').removeClass('act-menu-bar');
				$('.header-top-setting').removeClass('activedmenubar');
			}
			else{
				$('.header-top-setting .setting-container').addClass('act-menu-bar');
				$('.header-top-setting').addClass('activedmenubar');
			}
			return false;
		});
		$('#btn-h-t-s-close').click(function () {
			$('.header-top-setting .setting-container').removeClass('act-menu-bar');
			$('.header-top-setting').removeClass('activedmenubar');
			return false;
		});
		$('#close-menu a').click(function () {
			$('html').removeClass('nav-open');
			$('html').removeClass('nav-before-open');
			return false;
		});
		$('.quick-view-content .detailed div.title a').click(function () {
			var id_show = $(this).attr('href');
			$('.quick-view-content .detailed .data.content').hide();
			$(id_show).show();
			return false;
		});
		$('#back-top').click(function () {
			$('body,html').animate({
				scrollTop: 0
			}, 800);
			return false;
		});
		$('.click-show-menu').click(function() {
			if(!$('.click-open').hasClass('show-menu')){
				$('.click-open').addClass('show-menu');
				$('.click-show-menu').children('i').removeClass('icon-align-right').addClass('icon-x');
			}
			else{	
			$('.click-open').removeClass('show-menu');
				$('.click-show-menu').children('i').removeClass('icon-x').addClass('icon-align-right');
			}
			return false;
		});
	});

	$(document).on('click','.footer-links .title.mobile', function(event){
		$(this).next().slideToggle();
		$(this).toggleClass('clicked');
		$(this).addClass('customer_clicked');
	});
	if($(window).width() <  768)
	{
		$('.footer-links .title').addClass('mobile');
		$('.footer-links .title').next().hide();
	}
	$(window).on('resize', function(event){
		if($(window).width() <  768)
		{
			$('.footer-links .title').addClass('mobile');
			if(!$('.footer-links .title').hasClass('customer_clicked'))
			{
				$('.footer-links .title').next().hide();
			}
		}else
		{
			$('.footer-links .title').removeClass('mobile');
			$('.footer-links .title').next().show();
		}
	});

	var scrolled_sticky = false;
	var scrolled_back = false;
    /* Form with auto submit feature */
    $(window).scroll(function () {
		if ($(this).scrollTop() > 100 && !scrolled_back) {
			$('#back-top').fadeIn();
			scrolled_back = true;
		}
		if ($(this).scrollTop() <= 100 && scrolled_back) {
			$('#back-top').fadeOut();
			scrolled_back = false;
		}
		var start = $(".header-container").outerHeight();
		var width_window = $(window).width();
		if ($(this).scrollTop() > start && !scrolled_sticky && width_window >= 768 && $('.enabled-header-sticky').length){  
			$(".header-wrapper-sticky").addClass("enable-sticky");
			$(".minicart-wrapper").addClass("enable-sticky");
			$(".header-wrapper-sticky > .container-header-sticky").addClass("container");
			scrolled_sticky = true;
			var width_container = $(".container-header-sticky.container").outerWidth();
			var fixed_right = (width_window - width_container) / 2;
			fixed_right = fixed_right + 20;
			$(".minicart-wrapper.enable-sticky").css('right', fixed_right+'px');
		}
		if($(this).scrollTop() <= start && scrolled_sticky && width_window >= 768 && $('.enabled-header-sticky').length){
			scrolled_sticky = false;
			$(".header-wrapper-sticky").removeClass("enable-sticky");
			$(".minicart-wrapper").removeClass("enable-sticky");
			$(".header-wrapper-sticky > .container-header-sticky").removeClass("container");
			$(".minicart-wrapper").css('right', 'initial');
		}
		if ($(this).scrollTop() > start && !scrolled_sticky && width_window >= 768 && $('.enabled-header-sticky-right-to-left').length){
			scrolled_sticky = true;
			$(".enabled-header-sticky-right-to-left").addClass("enable-sticky");
		}
		if($(this).scrollTop() <= start && scrolled_sticky && width_window >= 768 && $('.enabled-header-sticky-right-to-left').length){
			scrolled_sticky = false;
			$(".enabled-header-sticky-right-to-left").removeClass("enable-sticky");
		}
	});
});