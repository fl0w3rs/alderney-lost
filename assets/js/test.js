$(() => {
    setInterval(() => {
        if (test_started === true) {
            axios.post('/api/test/keepalive').then((response) => {

                if (response.data.status == 'success') {
                    $('#test-block').html(response.data.content)
                    test_started = false
                } else if (response.data.status == 'keepalive_ok') {
                    $('#test-time').html(response.data.time_left)
                }
            })
        }
    }, 1000);
})

window.onunload = function () {
    fetch(base_link + '/api/test/keepalive', {
        method: 'POST',
        keepalive: true,
        body: JSON.stringify({ hidden: true })
    });
};

var test_started = false;

const initTextAreas = () => {

    document.querySelectorAll('.form-outline').forEach((formOutline) => {
        new mdb.Input(formOutline).init();
    });
}

window.startTest = (id) => {
    axios.post('/api/test/' + id + '/start').then((response) => {

        if (response.data.status == 'success') {
            $('#test-block').html(response.data.content)

            initTextAreas();

            test_started = true
        } else if (response.data.status == 'error') {
            toast.error('Произошла непредвиденная ошибка')
            console.error(response)
        }
    })
}

window.submitAnswer = () => {
    console.log($('input[name=answer]').val())
    axios.post('/api/test/answer', { answer: getAnswer() }).then((response) => {

        if (response.data.status == 'success') {
            $('#test-block').html(response.data.content)

            initTextAreas();

            if (response.data.test_status != 1) test_started = false
        } else if (response.data.status == 'error') {
            toast.error('Произошла непредвиденная ошибка')
            console.error(response)
        }
        // console.log(response); console.log(response.data.admin)
    })
}


const getAnswer = () => {
    let type = $('input[name="answer"], textarea[name="answer"]').attr('type')
    let textarea = $('textarea[name="answer"]');
    if (type == 'radio') {
        return $('input[name="answer"]:checked').val()
    } else if (type == 'checkbox') {
        let answers = []
        $('input:checkbox[name="answer"]:checked').each(function () {
            answers.push($(this).val());
        });
        return answers
    } else if (textarea) {
        return textarea.val();
    }
}