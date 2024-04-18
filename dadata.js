if(window.location.pathname==="/personal/order/make/"){
	console.log("оформление заказа");
	$(document).ready(function() {
		$("<div class='menu' ></div>").insertAfter($("input[name='ORDER_PROP_9']"));
		const INN = document.querySelector('input[name="ORDER_PROP_9"]');
		INN.addEventListener('blur', (event) => {


			setTimeout(() => {
	$('.menu').empty();
	}, 100);

		});
		INN.addEventListener('focus', () => {
			if (INN.value != '') {
				getDaDataSuggestions(INN.value);

			}
		});
	});
	$(document).ready(function() {
		$('input[name="ORDER_PROP_9"]').on('input', function() {

			var inputValue = $(this).val();

			// Вызов функции для получения подсказок, если введено достаточно символов
			if (inputValue.length >= 1) {
				getDaDataSuggestions(inputValue);

			}
			if (inputValue.length === 0) {
				$('.menu').empty();

			}

		});
	});

	function highlightMatch(text, query) {
		const escapedQuery = query.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"); // Экранирование специальных символов
		return text.replace(new RegExp(escapedQuery, 'gi'), (match) => `<span class="highlight">${match}</span>`);
	}

	function getDaDataSuggestions(query) {
		$.ajax({
			type: "POST",
			url: "https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party", // URL для подсказок по организациям
			contentType: "application/json",
			headers: {
				"Authorization": "Token 0fb957a4e89df258c431a85f6c045d5f8d7281b1"
			},
			data: JSON.stringify({
				query: query
			}),
			success: function(response) {

				$('.menu').empty();
				response.suggestions.forEach(function(suggestion) {
					const highlightedValue = highlightMatch(suggestion.value, query);
					const highlightedInn = highlightMatch(suggestion.data.inn, query);
					const suggestionElement = $(`<div class="suggestion-item"> <div class="name-inn">${highlightedValue}</div> <div class="inn">${highlightedInn}</div> </div>`);

					suggestionElement.on('click', function() {
						$('input[name="ORDER_PROP_9"]').val(suggestion.data.inn); // Заполнение input выбранной подсказкой
						$('.menu').empty(); // Очищаем контейнер подсказок после выбора
					});

					$('.menu').append(suggestionElement); // Добавление подсказки в контейнер
				});
				const INPUT_INN = document.querySelector('input[name="ORDER_PROP_9"]');
				if (INPUT_INN.value === '') {
					$('.menu').empty();
				}

				// console.log(response);

			}
		});
	}
}