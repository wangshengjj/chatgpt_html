(function ($) {
    var defaults = {
        content: '',
        success: null
    };
    $.extend({
        copy: function (option) {
            var options = $.extend({}, defaults, option);
            var content = options.content;
            if (content == '') {
                return false;
            }
            var u = navigator.userAgent;
            var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/);
            var dom = null;
            if (isiOS) {
                dom = document.createElement('a');
                dom.setAttribute('id', "selector");
                dom.setAttribute('style', 'position:absolute;top:-9999px;left:-9999px;');
                dom.innerHTML = content;
                $('body').append(dom);
                var copyDOM = document.querySelectorAll('#selector');
                var range = document.createRange();
                range.selectNode(copyDOM[0]);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand('copy');
            } else {
                dom = document.createElement('textarea');
                dom.style = 'position:absolute;top:-9999px;left:-9999px;';
                dom.setAttribute('id', 'selector');
                dom.setAttribute('readonly', 'readonly');
                dom.innerHTML = content;
                $('body').append(dom);
                $('#selector').select();
                document.execCommand('copy', false, null);
            }
            $('#selector').remove();
            if (options.success) options.success();
        }
    });
})(jQuery);