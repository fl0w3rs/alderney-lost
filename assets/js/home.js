$('.test-content').hover(function () {
    $(this).prev('.test-button-overlay').fadeIn(200)
})

$('.test-button-overlay').hover(() => {}, function () {
    $(this).fadeOut(200, () => {
        $(this).attr("style", "display: none !important");
    })
})