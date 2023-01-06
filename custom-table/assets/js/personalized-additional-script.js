(function ($) {
    $(document).ready(function () {
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


        // On réinitialise les données
        // On réaffiche la précédente image si l'utilisateur n'avait pas terminé
        if (localStorage.getItem('picture-uploaded')) {
            const object = JSON.parse(localStorage.getItem("picture-uploaded"))

            if (object.status == "success") {
                let url = object.picture
                $('.tabloide-card-img').css("backgroundImage", "url('" + url + "')");
                $('.tabloide-link-upload').val(url);
            }
        }

        // Position du tableau (Vertical ou horizontal)
        $("#tabloide-disposition-check").on("change", (event) => {
            if (event.target.checked) {
                $(".tabloide-card-img").hide()
                $(".tabloide-card-img").addClass("tabloide-card-img-vertical");
                setTimeout(() => {
                    $(".tabloide-card-img").show()
                }, 50);
            } else {
                $(".tabloide-card-img").hide()
                $(".tabloide-card-img").removeClass("tabloide-card-img-vertical");
                setTimeout(() => {
                    $(".tabloide-card-img").show()
                }, 50);
            }
        });

        // Carousel
        let initial = 100

        $("body").on("click", function (evt) {
            if (evt.target.closest('.personalized-arrow-right')) {
                if (initial <= 200) {
                    if (initial >= 100) {
                        $('.personalized-arrow-left').show();
                    }
                    $(".personalized-pictures").css("transform", "translateX(-" + initial + "%)")
                    if (initial < 200) {
                        initial += 100
                    } else if (initial == 200) {
                        $('.personalized-arrow-right').hide();
                    }
                }
            } else if (evt.target.closest('.personalized-arrow-left')) {
                if (initial >= 100) {
                    initial -= 100
                    if (initial <= 200) {
                        $('.personalized-arrow-right').show();
                    }
                    $(".personalized-pictures").css("transform", "translateX(-" + initial + "%)")
                    if (initial == 0) {
                        $('.personalized-arrow-left').hide();
                    }
                }
            }
        });

        $(".tabloide-validate").click(function (evt) {
            evt.preventDefault();
            if ($("#tabloide-upload").val() == "" && $(".tabloide-link-upload").val() == "") {
                personalized_notice("error", i18n.picture_empty)
                return
            }
            if (localStorage.getItem('picture-uploaded')) {
                if (evt.target) {
                    let step = evt.target.dataset.stepNext
                    if (step == 2) {
                        if (localStorage.getItem('picture-uploaded')) {
                            const obj = JSON.parse(localStorage.getItem("picture-uploaded"))
                            if (obj.status == "error") {
                                personalized_notice("error", i18n.picture_empty)
                                return
                            }
                            $(".tabloide-paginate-step").css("transform", "translateX(-100%)")
                            $(".progress-bar-enabled").removeClass("progress-bar-step-1")
                            $(".progress-bar-enabled").addClass("progress-bar-step-2")
                        }
                    }

                }
            }
        });

        // Return to previous step
        $(".return-step-prev").click(function (evt) {
            evt.preventDefault();
            if (evt.target.closest('.return-step-prev')) {
                let prev = evt.target.dataset.stepPrev
                if (prev == 0) {
                    $(".progress-bar-enabled").removeClass("progress-bar-step-2")
                    $(".progress-bar-enabled").removeClass("progress-bar-step-3")
                    $(".progress-bar-enabled").addClass("progress-bar-step-1")
                } else if (prev == 100) {
                    $(".progress-bar-enabled").removeClass("progress-bar-step-1")
                    $(".progress-bar-enabled").removeClass("progress-bar-step-3")
                    $(".progress-bar-enabled").addClass("progress-bar-step-2")
                }
                $(".tabloide-paginate-step").css("transform", "translateX(-" + prev + "%)")
            }
        });


        $.each($('.variation-Image > p'), function (indexInArray, valueOfElement) {
            let closest = valueOfElement.closest('.cart_item');
            picture = closest.querySelector('.variation-Image > p').innerHTML
            closest.querySelector("img").src = picture
            closest.querySelector("img").srcset = picture
        });

    });
})(jQuery)
