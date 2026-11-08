(function () {
	'use strict';

	function sortWidget(widget, direction) {
		var list = widget.querySelector('.wcp-product-list');
		if (!list) {
			return;
		}

		var items = Array.prototype.slice.call(list.querySelectorAll('[data-wcp-price]'));
		items.sort(function (a, b) {
			var difference = Number(a.getAttribute('data-wcp-price')) - Number(b.getAttribute('data-wcp-price'));
			if (direction === 'desc') {
				difference = -difference;
			}
			return difference || Number(a.getAttribute('data-wcp-order')) - Number(b.getAttribute('data-wcp-order'));
		});

		items.forEach(function (item) {
			list.appendChild(item);
		});
	}

	function initializeSorters() {
		document.querySelectorAll('.woo-category-products').forEach(function (widget) {
			var select = widget.querySelector('.wcp-sort-select');
			if (select) {
				sortWidget(widget, select.value);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeSorters);
	} else {
		initializeSorters();
	}

	document.addEventListener('change', function (event) {
		var select = event.target;
		if (select && select.classList && select.classList.contains('wcp-sort-select')) {
			var widget = select.closest('.woo-category-products');
			if (widget) {
				sortWidget(widget, select.value);
			}
		}
	});
})();
