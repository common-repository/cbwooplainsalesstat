(function ($) {
    "use strict";

    $(document).ready(function ($) {
        $(".cbpalinstatdate").datepicker({
            dateFormat: "dd-mm-yy"
        });

        $('.cbpalinstatchange').click(function (e) {
            e.preventDefault();
            var date           = $(".cbpalinstatdate").val();
            var locationofdate = $('.cbpalinstatlocation').val();
            window.location    = locationofdate + '&cbstatdate=' + date;
        });

        $('.cbpalinstatbetweendates').click(function (e) {
            e.preventDefault();
            var startdate      = $(".cbstatdatestart").val();
            var enddate        = $(".cbstatdateend").val();
            var view           = $('input[name="cbstatview"]:checked').val();
            var locationofdate = $('.cbbetweendatespage').val();
            window.location    = locationofdate + '&cbstatdatestart=' + startdate + '&cbstatdateend=' + enddate + '&cbreportview=' + view;
        });

    });
}(jQuery));