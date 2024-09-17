/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

executeFunc(function initAvitoPromotion() {

    const avitoPromotionForm = document.forms.avito_promotion_form;

    let categoryElement = document.getElementById('avito_promotion_form_category');

    if (categoryElement === null) {
        return false;
    }

    categoryElement.addEventListener('change', function (event) {

        document.getElementById('categoryName').innerHTML = '';

        changeCategory(avitoPromotionForm);
    })

    // кнопка добавления элемента в коллекцию
    let filterCollectionBtnAdd = document.getElementById('filterCollection_add');

    const form = new FormData(document.forms.avito_promotion_form);

    currentCategory = form.get('avito_promotion_form[category]')

    document.getElementById('filterCollection_add').disabled = true

    // для отслеживания удаленных элементов коллекции
    deleteItemsStorage = new Map();

    filterCollectionBtnAdd.addEventListener('click', addItem);

    const currentDeleteBtns = document.getElementById('filterCollection').querySelectorAll('.del-item-filter');

    if (currentDeleteBtns) {
        deleteItem(currentDeleteBtns)
    }

    /** Инициируем календарь */
    document.querySelectorAll('.js-datepicker').forEach((datepicker) => {
        MCDatepicker.create({
            el: '#' + datepicker.id,
            bodyType: 'modal', // ‘modal’, ‘inline’, or ‘permanent’.
            autoClose: false,
            closeOndblclick: true,
            closeOnBlur: false,
            customOkBTN: 'OK',
            customClearBTN: datapickerLang[$locale].customClearBTN,
            customCancelBTN: datapickerLang[$locale].customCancelBTN,
            firstWeekday: datapickerLang[$locale].firstWeekday,
            dateFormat: 'DD.MM.YYYY',
            customWeekDays: datapickerLang[$locale].customWeekDays,
            customMonths: datapickerLang[$locale].customMonths,
        });
    });

    return true;
});

function addItem() {

    const predicateSelect = document.getElementById('avito_promotion_form_predicatePrototype');

    if (predicateSelect) {
        predicateSelect.value = predicateData

        // predicateSelect.disabled = true
    }

    // блок с элементами коллекции
    let filterCollectionBlock = document.getElementById('filterCollection');

    // глобальный индекс текущего элемента коллекции
    itemKey = this.dataset.index

    // проверка на наличие ранее удаленных элементов
    if (deleteItemsStorage.size > 0) {

        let last = '';

        // получаем последний индекс удаленного элемента
        deleteItemsStorage.forEach(function (value) {

            last = value;

        });

        // меняем глобальный индекс текущего элемента коллекции на индекс удаленного элемента
        itemKey = last

        // удаляем элемент из хранилища удаленных элементов
        deleteItemsStorage.delete('key' + last)
    }

    // id прототипа из кнопки добавления элемента в коллекцию
    let prototypeName = this.dataset.prototype;

    // элемент с прототипом
    const prototypeElement = document.getElementById(prototypeName);

    // контент прототипа
    let prototypeContent = prototypeElement.dataset.prototype;

    // увеличиваем индекс элемента коллекции
    let index = parseInt(this.dataset.index) + 1;

    // добавляем текущее значение к кнопке для отслеживания увеличения элементов коллекции
    this.setAttribute('data-index', index)

    // let count = filterCollectionBlock.getElementsByClassName('item-filter').length;

    let limit = 4;

    // ограничение максимального количество элементов коллекции
    if (parseInt(this.dataset.index) > limit) {

        this.setAttribute('data-index', limit)

        return;
    }

    // Добавление индекса для элемента коллекции по заполнителю из формы prototype_name => '__filters__'
    prototypeContent = prototypeContent.replace(/__filters__/g, itemKey);

    const parser = new DOMParser();
    const newPrototypeResult = parser.parseFromString(prototypeContent, 'text/html');

    // ---добавление новых элементов из ответа сервера
    let prototypePreProperty = newPrototypeResult.getElementById('prototypeProperty').querySelector('input');

    prototypePreProperty.setAttribute('value', currentPropertyData)

    let prototypePreValue = newPrototypeResult.getElementById('prototypeValue').querySelector('input');
    prototypePreValue.setAttribute('value', preValueData)

    let prototypePredicate = newPrototypeResult.getElementById('prototypePredicate').querySelector('input');
    prototypePredicate.setAttribute('value', predicateData)
    // ---

    // Вставляем новую коллекцию
    // Формируем блок для элемента коллекции
    let itemDiv = document.createElement('div');
    itemDiv.setAttribute('id', 'avito_promotion_form_filters_' + itemKey);
    itemDiv.classList.add('item-filter');

    // вставляем значения для элемента коллекции
    itemDiv.append(prototypePreProperty);
    itemDiv.append(prototypePreValue);
    itemDiv.append(prototypePredicate);

    // блок с описанием элемента коллекции
    let newTitle = document.createElement('div');
    newTitle.classList.add('w-100');
    newTitle.innerHTML = titleItem();

    // селект с категориями
    const categorySelect = document.getElementById('avito_promotion_form_category');

    // название категории
    let categoryTitle = document.getElementById('categoryName');

    // меняем название категории на текущее значение из списка
    if (categorySelect.querySelector(`[value="${categoryData}"]`).innerHTML) {
        categoryTitle.innerHTML = categorySelect.querySelector(`[value="${categoryData}"]`).innerHTML;
    }

    itemDiv.append(newTitle);

    filterCollectionBlock.append(itemDiv);

    let deleteBtns = filterCollectionBlock.querySelectorAll('.del-item-filter');

    deleteItem(deleteBtns)
}

//-------------------------------------

function deleteItem(buttons) {

    buttons.forEach(function (btn) {

        btn.addEventListener('click', function () {

            // элемент для удаления
            const itemForDelete = document.getElementById(btn.id.replace(/delete-/g, ''));

            // индекс для удаления
            const deleteIndex = btn.id.replace(/delete-avito_promotion_form_filters_/g, '');
            // const deleteIndex = parseInt(btn.id.match(/\d+/));

            // добавляем индекс удаленного элемента для отслеживания
            deleteItemsStorage.set('key' + deleteIndex, deleteIndex)

            // если элемент удалился - получаем текущий индекс коллекции и уменьшаем его
            if (itemForDelete) {

                itemForDelete.remove()

                let addBtn = document.getElementById('filterCollection_add');

                const newIndex = parseInt(addBtn.dataset.index) - 1;

                addBtn.setAttribute('data-index', newIndex)
            }
        });
    });
}

//-------------------------------------

function titleItem() {

    const titleTemplate = document.getElementById('title_prototype');

    let newTitle = titleTemplate.dataset.prototype;

    newTitle = newTitle.replace(/__key__/g, itemKey)
    newTitle = newTitle.replace(/__number__/g, parseInt(itemKey) + 1)

    // название preProperty
    const prePropertySelect = document.getElementById('avito_promotion_form_preProperty');

    if (prePropertySelect.querySelector(`[value="${currentPropertyData}"]`).innerHTML) {
        newTitle = newTitle.replace(/__title__property__/g, prePropertySelect.querySelector(`[value="${currentPropertyData}"]`).innerHTML)
    }

    // название predicatePrototype
    const predicateSelect = document.getElementById('avito_promotion_form_predicatePrototype');

    newTitle = newTitle.replace(/__title__predicate__/g, predicateSelect.querySelector(`[value="${predicateData}"]`).innerHTML)

    // название preValue
    const preValueForm = document.getElementById('avito_promotion_form_preValue');

    // checkbox
    if (preValueForm.value) {

        // значение для шипов
        if (preValueForm.value === '1') {
            newTitle = newTitle.replace(/__title__value__/g, 'есть');
        } else {
            newTitle = newTitle.replace(/__title__value__/g, preValueForm.value);
        }

        return newTitle;
    }

    const preValue = preValueForm.querySelector(`[value="${preValueData}"]`);

    // input
    if (preValue.value) {
        let parent = preValueForm.querySelector(`[value="${preValueData}"]`).parentElement

        newTitle = newTitle.replace(/__title__value__/g, parent.querySelector('label').innerHTML)

        return newTitle;
    }

    // select
    if (preValueForm.querySelector(`[value="${preValueData}"]`).innerHTML) {
        newTitle = newTitle.replace(/__title__value__/g, preValueForm.querySelector(`[value="${preValueData}"]`).innerHTML)

        return newTitle;
    }
}

//-------------------------------------

async function changeCategory(form) {

    const data = new FormData(form);

    // Удаляем токен из формы
    data.delete(form.name + '[_token]');

    categoryData = data.get('avito_promotion_form[category]');

    // postForm(form)
    // return;

    await fetch(form.action, {
        method: form.method,
        cache: 'no-cache',
        credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: data
    })

        .then((response) => {
            if (response.status !== 200) {
                return false;
            }

            return response.text();
        })

        .then((data) => {
            if (data) {

                // удаление элементов коллекции
                if (!currentCategory || categoryData !== currentCategory) {
                    let currentFilterCollection = document.getElementById('filterCollection').querySelectorAll('.item-filter')

                    if (currentFilterCollection.length > 0) {
                        currentFilterCollection.forEach(function (item) {
                            document.getElementById('filterCollection_add').dataset.index = 0
                            item.remove()
                        });
                    }
                }

                const parser = new DOMParser();
                const result = parser.parseFromString(data, 'text/html');

                let currentPreValue = document.getElementById('avito_promotion_form_preValue');

                if (currentPreValue.type !== 'hidden') {
                    document.getElementById('avito_promotion_form_preValue').replaceWith(result.getElementById('avito_promotion_form_preValue'))
                }

                let newDiv = result.getElementById('preProperty');

                let current = document.getElementById('avito_promotion_form_preProperty');

                // select из события POST_SUBMIT
                let postSubmit = result.getElementById('avito_promotion_form_preProperty');

                if (postSubmit) {

                    // заменяем текущий элемент с id avito_promotion_form_preProperty на новый
                    current.replaceWith(postSubmit);
                }

                postSubmit.addEventListener('change', function (event) {
                    document.getElementById('preValueContainer').remove();
                    document.getElementById('predicatePrototypeContainer').remove();

                    changePreProperty(document.forms.avito_promotion_form);
                })

            }
        });
}

//-------------------------------------

async function changePreProperty(form) {
    disabledElementsForm(form);

    const data = new FormData(form);

    // Удаляем токен из формы
    data.delete(form.name + '[_token]');

    currentPropertyData = data.get('avito_promotion_form[preProperty]');

    await fetch(form.action, {
        method: form.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        .then((response) => {
            if (response.status !== 200) {
                return false;
            }

            return response.text();
        })

        .then((data) => {
            if (data) {

                const parser = new DOMParser();
                const result = parser.parseFromString(data, 'text/html');

                // preValue из POST_SUBMIT
                let postSubmit = result.getElementById('preValue');

                if (postSubmit) {
                    // заменяем текущий элемент с id avito_promotion_form_preProperty на новый
                    document.getElementById('preValue').replaceWith(postSubmit);
                }

                postSubmit.addEventListener('change', function (event) {

                    changePreValue(document.forms.avito_promotion_form);
                })

                // predicatePrototype из POST_SUBMIT
                let postSubmitPredicate = result.getElementById('predicatePrototype');

                if (postSubmitPredicate) {
                    // заменяем текущий элемент с id на новый
                    document.getElementById('predicatePrototype').replaceWith(postSubmitPredicate);
                }

                const form = new FormData(document.forms.avito_promotion_form);
                predicateData = form.get('avito_promotion_form[predicatePrototype]');

                postSubmitPredicate.addEventListener('change', function (event) {

                    changePredicate(document.forms.avito_promotion_form);
                })
            }
        });
}

//-------------------------------------

function changePredicate(form) {
    const data = new FormData(form);

    predicateData = data.get('avito_promotion_form[predicatePrototype]');
}

function changePreValue(form) {
    const data = new FormData(form);

    // Удаляем токен из формы
    data.delete(form.name + '[_token]');

    preValueData = data.get('avito_promotion_form[preValue]');
    console.log(preValueData)

    // значение для шипов
    if (preValueData === '1') {
        preValueData = 'true'
    }

    if (preValueData) {
        document.getElementById('filterCollection_add').disabled = false
        enableElementsForm(form);
    } else {
        document.getElementById('filterCollection_add').disabled = true
    }
}