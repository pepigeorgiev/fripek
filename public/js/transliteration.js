$(document).ready(function() {
    // Mapping of Latin to Cyrillic letters for Macedonian
    const translitMap = {
        'a': 'а', 'b': 'б', 'v': 'в', 'g': 'г', 'd': 'д', 'e': 'е', 'zh': 'ж', 'z': 'з', 
        'i': 'и', 'j': 'ј', 'k': 'к', 'l': 'л', 'm': 'м', 'n': 'н', 'o': 'о', 'p': 'п', 
        'r': 'р', 's': 'с', 't': 'т', 'u': 'у', 'f': 'ф', 'h': 'х', 'c': 'ц', 'ch': 'ч', 
        'sh': 'ш', 'dj': 'џ', 'gj': 'ѓ', 'kj': 'ќ', 'z': 'ж', 'c': 'ч', 's':'ш' 
    };

    // Function to convert Latin to Cyrillic
    function transliterate(input) {
        return input.toLowerCase().replace(/ch|sh|dj|gj|kj|zh|[a-z]/g, function(match) {
            return translitMap[match] || match;
        });
    }

    // Custom matcher for Select2
    function customMatcher(params, data) {
        if ($.trim(params.term) === '') {
            return data;
        }

        const term = transliterate(params.term);
        const text = transliterate(data.text);

        console.log('Searching for:', term, 'in:', text);

        if (text.includes(term)) {
            return data;
        }

        return null;
    }

    // Initialize Select2 with custom matcher
    function initializeSelect2(selector) {
        $(selector).select2({
            placeholder: 'Пребарувај компанија...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            dropdownParent: $('body'),
            matcher: customMatcher
        });
    }

    // Example usage: Initialize Select2 on elements with class 'company-select'
    initializeSelect2('.company-select');
}); 