
$(function () {
$('[data-toggle="popover"]').popover();

    $('.fa-minus').on('click', function (e) {
        e.preventDefault();
        $that = $(this);
        $that.closest('.card').find('.card-body').toggleClass('d-none');

    });
    $('.fa-times').on('click', function (e) {
        e.preventDefault();
        $that = $(this);
        $that.closest('.card').remove();

    });
function werty(){
    
}
});
