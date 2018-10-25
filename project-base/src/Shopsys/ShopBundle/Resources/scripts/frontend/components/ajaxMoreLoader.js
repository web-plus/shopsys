(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.ajaxMoreLoader = Shopsys.ajaxMoreLoader || {};
    Shopsys.ajaxMoreLoader.AjaxMoreLoader = Shopsys.ajaxMoreLoader.AjaxMoreLoader || {};

    Shopsys.ajaxMoreLoader.AjaxMoreLoader = function ($wrapper) {
        var self = this;
        var $loadMoreButton;
        var $currentList;
        var $paginationToItemSpan;

        var totalCount;
        var pageSize;
        var page;
        var paginationToItem;
        var type;

        this.init = function () {
            $loadMoreButton = $wrapper.filterAllNodes('.js-load-more-button');
            $currentList = $wrapper.filterAllNodes('.js-list');
            $paginationToItemSpan = $wrapper.filterAllNodes('.js-pagination-to-item');

            totalCount = $loadMoreButton.data('total-count');
            pageSize = $loadMoreButton.data('page-size');
            page = $loadMoreButton.data('page');
            paginationToItem = $loadMoreButton.data('pagination-to-item');
            type = $loadMoreButton.data('type');

            updateLoadMoreButton();
            $loadMoreButton.on('click', onClickLoadMoreButton);
        };

        this.reInit = function () {
            self.init();
        };

        var onClickLoadMoreButton = function () {
            $(this).hide();

            var requestData = {
                page: page + 1
            };

            if (typeof type !== 'undefined') {
                requestData['type'] = type;
            }

            Shopsys.ajax({
                loaderElement: $wrapper,
                type: 'GET',
                url: document.location,
                data: requestData,
                success: function (data) {
                    var $response = $($.parseHTML(data));
                    var $nextProducts = $response.find('.js-list > li');
                    $currentList.append($nextProducts);
                    page++;
                    paginationToItem += $nextProducts.length;
                    $paginationToItemSpan.text(paginationToItem);
                    updateLoadMoreButton();

                    Shopsys.register.registerNewContent($nextProducts);
                }
            });
        };

        var updateLoadMoreButton = function () {
            var remaining = totalCount - page * pageSize;
            var loadNextCount = remaining >= pageSize ? pageSize : remaining;
            var buttonText = Shopsys.translator.transChoice(
                '{1}Load next %loadNextCount% product|[2,Inf]Load next %loadNextCount% products',
                loadNextCount,
                {'%loadNextCount%': loadNextCount}
            );

            $loadMoreButton
                .val(buttonText)
                .toggle(remaining > 0);
        };

    };

})(jQuery);
