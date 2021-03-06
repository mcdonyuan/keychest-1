/**
 * Created by dusanklinec on 01.06.17.
 */

// Blocs.js
function setUpSpecialNavs() {
    function a() {
        var a = "fadeInRight", b = 0, c = 60;
        $(".blocsapp-special-menu blocsnav").hasClass("fullscreen-nav") ? (a = "fadeIn", c = 100) : $(".blocsapp-special-menu").hasClass("nav-invert") && (a = "fadeInLeft"), $(".blocsapp-special-menu blocsnav li").each(function () {
            $(this).parent().hasClass("dropdown-menu") ? $(this).addClass("animated fadeIn") : (b += c, $(this).attr("style", "animation-delay:" + b + "ms").addClass("animated " + a))
        })
    }

    $(".navbar-toggle").click(function (b) {
        var c = $(this).closest("nav"), d = c.find("ul.site-navigation"), e = d.clone();
        if (d.parent().hasClass("nav-special"))if (b.stopPropagation(), $(this).hasClass("selected-nav")) $(".blocsapp-special-menu blocsnav").removeClass("open"), $(".selected-nav").removeClass("selected-nav"), setTimeout(function () {
            $(".blocsapp-special-menu").remove(), $("body").removeClass("lock-scroll"), $(".selected-nav").removeClass("selected-nav")
        }, 300); else {
            $(this).addClass("selected-nav");
            var f = c.attr("class").replace("navbar", "").replace("row", ""),
                g = d.parent().attr("class").replace("navbar-collapse", "").replace("collapse", "");
            ($(".content-tint").length = -1) && $("body").append('<div class="content-tint"></div>'), e.insertBefore(".page-container").wrap('<div class="blocsapp-special-menu ' + f + '"><blocsnav class="' + g + '">'), $("blocsnav").prepend('<a class="close-special-menu animated fadeIn" style="animation-delay:0.5s;"><div class="close-icon"></div></a>'), a(), setTimeout(function () {
                $(".blocsapp-special-menu blocsnav").addClass("open"), $(".content-tint").addClass("on"), $("body").addClass("lock-scroll")
            }, 10)
        }
    }), $("body").on("mousedown touchstart", ".content-tint, .close-special-menu", function (a) {
        $(".content-tint").removeClass("on"), $(".selected-nav").click(), setTimeout(function () {
            $(".content-tint").remove()
        }, 10)
    }).on("click", ".blocsapp-special-menu a", function (a) {
        $(a.target).closest(".dropdown-toggle").length || $(".close-special-menu").mousedown()
    })
}

function hideNavOnItemClick() {
    $(".site-navigation a").click(function (a) {
        $(a.target).closest(".dropdown-toggle").length || $(".navbar-collapse").collapse("hide")
    })
}

/**
 * detect IE
 * returns version of IE or false, if browser is not Internet Explorer
 */
function detectIE() {
    const ua = window.navigator.userAgent;

    const msie = ua.indexOf('MSIE ');
    if (msie > 0) {
        // IE 10 or older => return version number
        return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
    }

    const trident = ua.indexOf('Trident/');
    if (trident > 0) {
        // IE 11 => return version number
        const rv = ua.indexOf('rv:');
        return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
    }

    const edge = ua.indexOf('Edge/');
    if (edge > 0) {
        // Edge (IE 12+) => return version number
        return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
    }

    // other browser
    return false;
}

/**
 * Computes height of the children.
 * @param x
 */
function subHeightChildren(x){
    if (!x){
        return 0;
    }
    var subHeight = 0;
    x.children().each(function(xx){
        subHeight += $(this).outerHeight();
    });

    return subHeight;
}

/**
 * Height computation, IE friendly.
 * @param bsub
 */
function heightOfObject(bsub){
    if (!bsub){
        return 0;
    }

    // IE workaround, "initial" keyword is not working properly, set it to "auto"
    if (window.blocs && window.blocs.isIE) {
        if (bsub.hasClass('fill-bloc-bottom-edge')) {
            if (bsub.css('top') === 'initial') {
                bsub.css('top', 'auto');
            }
        }
    }

    const elemHeight = bsub.outerHeight();
    return elemHeight;
}

function setFillScreenBlocHeight() {
    $(".bloc-fill-screen").each(function (a) {
        const b = $(this);
        window.fillBodyHeight = 0;

        $(this).find(".container").each(function (a) {
            const bsub = $(this);
            const fillPadding = 2 * parseInt(bsub.css("padding-top"));
            const elemHeight = heightOfObject(bsub);
            b.hasClass("bloc-group") ? fillBodyHeight = fillPadding + elemHeight + 50
                    : fillBodyHeight = fillBodyHeight + fillPadding + elemHeight + 50;
        });

        $(this).css("height", getFillHeight() + "px")
    })
}

function getFillHeight() {
    var a = $(window).height();
    return a < fillBodyHeight && (a = fillBodyHeight + 100), a
}

function scrollToTarget(a) {
    1 == a ? a = 0 : 2 == a ? a = $(document).height() : (a = $(a).offset().top, $(".sticky-nav").length && (a -= $(".sticky-nav").height())), $("html,body").animate({scrollTop: a}, "slow"), $(".navbar-collapse").collapse("hide")
}

function animateWhenVisible() {
    hideAll(), inViewCheck(), $(window).scroll(function () {
        inViewCheck(), scrollToTopView(), stickyNavToggle()
    })
}

function setUpDropdownSubs() {
    $("ul.dropdown-menu [data-toggle=dropdown]").on("click", function (a) {
        a.preventDefault(), a.stopPropagation(), $(this).parent().siblings().removeClass("open"), $(this).parent().toggleClass("open");
        var b = $(this).parent().children(".dropdown-menu"), c = b.offset().left + b.width();
        c > $(window).width() && b.addClass("dropmenu-flow-right")
    })
}

function stickyNavToggle() {
    var a = 0, b = "sticky";
    if ($(".sticky-nav").hasClass("fill-bloc-top-edge")) {
        var c = $(".fill-bloc-top-edge.sticky-nav").parent().css("background-color");
        "rgba(0, 0, 0, 0)" == c && (c = "#FFFFFF"), $(".sticky-nav").css("background", c), a = $(".sticky-nav").height(), b = "sticky animated fadeInDown"
    }
    $(window).scrollTop() > a ? ($(".sticky-nav").addClass(b), "sticky" == b && $(".page-container").css("padding-top", $(".sticky-nav").height())) : ($(".sticky-nav").removeClass(b).removeAttr("style"), $(".page-container").removeAttr("style"))
}

function hideAll() {
    $(".animated").each(function (a) {
        $(this).closest(".hero").length || $(this).removeClass("animated").addClass("hideMe")
    })
}

function inViewCheck() {
    $($(".hideMe").get().reverse()).each(function (a) {
        var b = jQuery(this), c = b.offset().top + b.height(), d = $(window).scrollTop() + $(window).height();
        if (b.height() > $(window).height() && (c = b.offset().top), c < d) {
            var e = b.attr("class").replace("hideMe", "animated");
            b.css("visibility", "hidden").removeAttr("class"), setTimeout(function () {
                b.attr("class", e).css("visibility", "visible")
            }, .01)
        }
    })
}

function scrollToTopView() {
    $(window).scrollTop() > $(window).height() / 3 ? $(".scrollToTop").hasClass("showScrollTop") || $(".scrollToTop").addClass("showScrollTop") : $(".scrollToTop").removeClass("showScrollTop")
}

function setUpVisibilityToggle() {
    $(document).on("click", "[data-toggle-visibility]", function (a) {
        function d(a) {
            a.is("img") ? a.toggle() : a.slideToggle()
        }

        a.preventDefault();
        var b = $(this).attr("data-toggle-visibility");
        if (b.indexOf(",") != -1) {
            var c = b.split(",");
            $.each(c, function (a) {
                d($("#" + c[a]))
            })
        } else d($("#" + b))
    })
}

function setUpLightBox() {
    window.targetLightbox, $(document).on("click", "[data-lightbox]", function (a) {
        a.preventDefault(), targetLightbox = $(this);
        var b = targetLightbox.attr("data-lightbox"), c = targetLightbox.attr("data-autoplay"),
            d = '<p class="lightbox-caption">' + targetLightbox.attr("data-caption") + "</p>", e = "no-gallery-set";
        targetLightbox.attr("data-gallery-id") && (e = targetLightbox.attr("data-gallery-id")), targetLightbox.attr("data-caption") || (d = "");
        var f = "";
        1 == c && (f = "autoplay");
        var g = $('<div id="lightbox-modal" class="modal fade"><div class="modal-dialog"><div class="modal-content ' + targetLightbox.attr("data-frame") + ' blocs-lb-container"><button type="button" class="close close-lightbox" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><div class="modal-body"><a href="#" class="prev-lightbox" aria-label="prev"><span class="fa fa-chevron-left"></span></a><a href="#" class="next-lightbox" aria-label="next"><span class="fa fa-chevron-right"></span></a><img id="lightbox-image" class="img-responsive" src="' + b + '"><div id="lightbox-video-container" class="embed-responsive embed-responsive-16by9"><video controls ' + f + ' class="embed-responsive-item"><source id="lightbox-video" src="' + b + '" type="video/mp4"></video></div>' + d + "</div></div></div></div>");
        $("body").append(g), ".mp4" == b.substring(b.length - 4) ? ($("#lightbox-image, .lightbox-caption").hide(), $("#lightbox-video-container").show()) : ($("#lightbox-image,.lightbox-caption").show(), $("#lightbox-video-container").hide()), $("#lightbox-modal").modal("show"), "no-gallery-set" == e ? (0 == $("a[data-lightbox]").index(targetLightbox) && $(".prev-lightbox").hide(), $("a[data-lightbox]").index(targetLightbox) == $("a[data-lightbox]").length - 1 && $(".next-lightbox").hide()) : (0 == $('a[data-gallery-id="' + e + '"]').index(targetLightbox) && $(".prev-lightbox").hide(), $('a[data-gallery-id="' + e + '"]').index(targetLightbox) == $('a[data-gallery-id="' + e + '"]').length - 1 && $(".next-lightbox").hide())
    }).on("hidden.bs.modal", "#lightbox-modal", function () {
        $("#lightbox-modal").remove()
    }), $(document).on("click", ".next-lightbox, .prev-lightbox", function (a) {
        a.preventDefault();
        var b = "no-gallery-set", c = $("a[data-lightbox]").index(targetLightbox), d = $("a[data-lightbox]").eq(c + 1);
        targetLightbox.attr("data-gallery-id") && (b = targetLightbox.attr("data-gallery-id"), c = $('a[data-gallery-id="' + b + '"]').index(targetLightbox), d = $('a[data-gallery-id="' + b + '"]').eq(c + 1)), $(this).hasClass("prev-lightbox") && (d = $('a[data-gallery-id="' + b + '"]').eq(c - 1), "no-gallery-set" == b && (d = $("a[data-lightbox]").eq(c - 1)));
        var e = d.attr("data-lightbox");
        if (".mp4" == e.substring(e.length - 4)) {
            var f = d.attr("data-autoplay"), g = "";
            1 == f && (g = "autoplay"), $("#lightbox-image, .lightbox-caption").hide(), $("#lightbox-video-container").show().html("<video controls " + g + ' class="embed-responsive-item"><source id="lightbox-video" src="' + e + '" type="video/mp4"></video>')
        } else $("#lightbox-image").attr("src", e).show(), $(".lightbox-caption").html(d.attr("data-caption")).show(), $("#lightbox-video-container").hide();
        targetLightbox = d, $(".next-lightbox, .prev-lightbox").hide(), "no-gallery-set" == b ? ($("a[data-lightbox]").index(d) != $("a[data-lightbox]").length - 1 && $(".next-lightbox").show(), $("a[data-lightbox]").index(d) > 0 && $(".prev-lightbox").show()) : ($('a[data-gallery-id="' + b + '"]').index(d) != $('a[data-gallery-id="' + b + '"]').length - 1 && $(".next-lightbox").show(), $('a[data-gallery-id="' + b + '"]').index(d) > 0 && $(".prev-lightbox").show())
    })
}

$(document).ready(function () {
    $("body").append('<div id="page-loading-blocs-notifaction"></div>');
    $(".bloc-fill-screen").css("height", $(window).height() + "px");
    $("#scroll-hero").click(function (a) {
        a.preventDefault();
        $("html,body").animate({scrollTop: $("#scroll-hero").closest(".bloc").height()}, "slow");
    });
    hideNavOnItemClick();
    setUpSpecialNavs();
    setUpDropdownSubs();
    setUpLightBox();
    setUpVisibilityToggle();

}), $(window).ready(function () {
    window.blocs = {
        isIE: detectIE()
    };

    setFillScreenBlocHeight();
    animateWhenVisible();
    $("#page-loading-blocs-notifaction").remove();

}).resize(function () {
    setFillScreenBlocHeight();

}), $(function () {
    $('[data-toggle="tooltip"]').tooltip()
});
