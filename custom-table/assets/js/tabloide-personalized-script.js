
(function ($) {
    // Notification (Error, or success message)
    function personalized_notice($type, $message) {
        if ($message != "") {
            if ($type == "error") {
                $(".tabloide-text-confirm").html($message)
                $(".tabloide-text-confirm").css("color", "red")
                setTimeout(() => {
                    $(".tabloide-text-confirm").html("")
                }, 3500);
            } else if ($type == "success") {
                $(".tabloide-text-confirm").html($message)
                $(".tabloide-text-confirm").css("color", "green")
                setTimeout(() => {
                    $(".tabloide-text-confirm").html("")
                }, 3500);
            }
        }
    }

    /* Check Image */
    function picture_uploaded(event) {
        event.preventDefault();

        // Nous supprimons l'ancienne image stocké sur le navigateur
        if (localStorage.getItem("picture-uploaded")) {
            localStorage.removeItem("picture-uploaded")
        }

        const formData = new FormData(event.target.closest('form'))
        formData.append('action', 'tabloide_check_picture_uploaded')
        $.ajax({
            type: "POST",
            url: ajax_object.ajaxurl,
            data: formData,
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                $('.tabloide-personalized-loading').show();
            },
            complete: function () {
                $(".tabloide-personalized-loading").hide();
            },
            success: function (response) {
                localStorage.setItem("picture-uploaded", JSON.stringify(response))
                if (response.status == "success") {
                    if (localStorage.getItem('picture-uploaded')) {
                        let url = response.picture
                        $('.tabloide-card-img').css("backgroundImage", "url('" + url + "')");
                        $('.tabloide-picture-input').val(url);
                    }
                    personalized_notice("success", ajax_object.picture_good)
                } else if (response.status == "error") {
                    personalized_notice("error", response.message)
                }
            },
        });
    }

    // Function that change table's size when clicking on different button (XS, S, M, L, ...)
    function selected_attribute(attributeName, evt) {
        evt.preventDefault();
        if (evt.target.classList.contains('tabloide-bull-' + attributeName + '')) {
            $.each($('.tabloide-bull-' + attributeName + ''), function (indexInArray, valueOfElement) {
                valueOfElement.classList.remove('attribute-' + attributeName + '-selected')
            });
            evt.target.classList.add('attribute-' + attributeName + '-selected')
        }
        let data = ""
        if (attributeName == "size") {
            if (evt.target.classList.contains('tabloide-bull-size')) {
                $.each($(".personalized-picture .tabloide-card-img"), function (indexInArray, valueOfElement) {
                    if (valueOfElement.classList.contains("tabloide-card-img-vertical")) {
                        valueOfElement.className = "tabloide-card-img tabloide-card-img-vertical"
                    } else {
                        valueOfElement.className = "tabloide-card-img"
                    }
                    $(".tabloide-card-img").addClass("d-none")
                    data = evt.target.dataset.sizeSlug
                    $(".tabloide-card-img").addClass('tabloide-size_' + data)
                    setTimeout(() => {
                        $(".tabloide-card-img").removeClass('d-none')
                    }, 100);
                });
            }
        } else if (attributeName == "support") {
            if (evt.target.classList.contains('tabloide-bull-support')) {
                data = evt.target.dataset.supportSlug
            }
        }

        $(".tabloide-" + attributeName + "-input").val(data)
    }

    // evt event
    // key return _price or _attribute_id
    function search_price_and_attribute_id(evt, key) {
        const formData = new FormData(evt.target.closest('form'))
        formData.append('action', 'tabloide_get_price_and_variation_id')
        formData.append('key', key)
        $.ajax({
            type: "POST",
            url: ajax_object.ajaxurl,
            data: formData,
            contentType: false,
            cache: false,
            processData: false,
            success: function (response) {
                localStorage.setItem("data-personalized-cart", JSON.stringify(response))
                if (response.status == "success") {
                    if (response.price == "error") {
                        $(".tabloide-paginate-step").css("transform", "translateX(-100%)")
                        personalized_notice("error", ajax_object.unavailable)
                        $(".progress-bar-enabled").removeClass("progress-bar-step-1")
                        $(".progress-bar-enabled").addClass("progress-bar-step-2")
                        return
                    }
                    $('.tabloid-price > span').html(response.price + " €")
                    $('#tabloide-personalized-attribute-id').val(response.variation_id)
                    $(".tabloide-paginate-step").css("transform", "translateX(-200%)")
                    $(".progress-bar-enabled").removeClass("progress-bar-step-1")
                    $(".progress-bar-enabled").removeClass("progress-bar-step-2")
                    $(".progress-bar-enabled").addClass("progress-bar-step-3")
                } else if (response.status == "error") {
                    $(".progress-bar-enabled").removeClass("progress-bar-step-1")
                    $(".progress-bar-enabled").addClass("progress-bar-step-2")
                    $(".tabloide-paginate-step").css("transform", "translateX(-100%)")
                    personalized_notice("error", ajax_object.error)
                }
            },
        });
    }

    /* Adding to cart */
    function adding_to_cart(event) {
        event.preventDefault();
        const formData = new FormData(event.target.closest('form'))
        formData.append('action', 'tabloide_add_to_cart_product_personalized')
        storage = localStorage.getItem("picture-uploaded")
        const obj = JSON.parse(storage)
        formData.append('personalized_id', obj.picture_id)
        formData.append('personalized_picture', obj.picture)
        $.ajax({
            type: "POST",
            url: ajax_object.ajaxurl,
            data: formData,
            contentType: false,
            cache: false,
            processData: false,
            success: function (response) {
                if (response.status == "success") {
                    personalized_notice("success", response.message)
                    localStorage.removeItem("picture-uploaded")
                    setTimeout(() => {
                        window.location.href = response.redirect
                    }, 4000);
                } else {
                    personalized_notice("error", response.message)
                }
            },
        });
    }


    $('.tabloide-link-click').click((evt) => {
        evt.preventDefault();
        $(".tabloide-link-upload-inner").removeClass("tabloide-link-upload-disabled");
        evt.target.remove();
    })

    // Select attribute
    $(".tabloide-size-display").click(function (evt) {
        selected_attribute("size", evt)
    });

    $(".tabloide-support-display").click(function (evt) {
        selected_attribute("support", evt)
    });

    // Show price and send attribute
    $(".tabloide-step-attribute").click(function (evt) {
        search_price_and_attribute_id(evt, "_price")
    });


    // Add to cart
    $("#single-add-to-cart").click(function (event) {
        adding_to_cart(event)
    });

    // Tabloide upload picture
    $(".tabloide-uploaded-validated").click(function (event) {
        picture_uploaded(event)
    });

    $("#tabloide-upload").change(function (event) {
        $(".tabloide-link-upload").val("")
        picture_uploaded(event)
    });

    $('.tabloide-delete-image').click(function (evt) {
        localStorage.removeItem('picture-uploaded')
        window.location.href = ""
    })
})(jQuery)