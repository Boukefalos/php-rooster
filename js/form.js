$(window).ready(function () {
    $('select#weeks').selectToUISlider({
        labels : 52,
        tooltip : false
    }).hide()

    $('select#week-from, select#week-to').selectToUISlider({
        labels : 18,
        labelSrc : 'text',
        tooltip : false
    }).hide()

    $('label[for="week-from"]').hide()
    $('label[for="week-to"]').text('Selecteer weken')
    
    $('select#weeks + div.ui-slider').bind('slide', function(e, ui) {
        slider = $('select#week-to + div.ui-slider');
        adjustWeeks(slider, 1, slider.slider('values', 0) + ui.value)
    }).css('width', '40%')

    $('select#week-to + div.ui-slider').bind('slide', function(e, ui) {
        weeks = parseInt($('select#weeks').val())
        if ($(ui.handle).attr('id').split('handle_')[1] == 'week-from') {
            adjustWeeks(this, 1, ui.value + weeks)
        } else {
            adjustWeeks(this, 0, ui.value - weeks)
        }
    }).css('width', '80%')

    function adjustWeeks(handle, index, value) {
        $(handle).slider('values', index, value)
    }

    $('select#group').multiselect({
        checkAllText : 'Selecteer alles',
        uncheckAllText : 'Selecteer niets',
        selectedText : '# van # geselecteerd',
        noneSelectedText : $('label[for="group"]').hide().text()
    })
})