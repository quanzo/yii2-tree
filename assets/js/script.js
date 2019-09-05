classTree = function (options) {
    
    var _self = this;
    var $ = jQuery;
    this.selected = null;
    this.prev_selected = null;

    this.settings = $.extend({
        url: "",
        urlMove: "",
        urlDelete: "",
        urlBranch: "",
        treeName: "",
        selector: "#tree",
        // treeElement: контейнер с деревом
        itemSelector: ".item",
        subitemSelector: ".subitems",
        expandSelector: ".expand",
        timeout: 5,
        allowSelect: true,
    }, options);

    //console.log("Start " + this.settings.treeName);

    if (typeof (this.settings.treeElement) == "undefined") {
        this.settings.treeElement = $(_self.settings.selector);        
    } else {
        this.settings.treeElement = $(this.settings.treeElement);
    }

    /**
     * загружаем ветку дерева с parent_id == id в dom элемент element
     * @param dom element
     * @param string treeName - имя дерева
     * @param string id - стартовый узел дерева
     * @return void
     */
    this.load = function (element, treeName, id, onSuccess = null, onError = null) { // в элемент загружаем ветку дерева
        //console.log("Load");
        if (_self.settings.url != "") {
            var ajaxParam = {
                url: _self.settings.url,
                data: {
                    "treeName": treeName,
                    "parentId": id,
                },
                success: function (data) {
                    element.innerHTML = data;
                    element.style.display = "block";
                    if (typeof (onSuccess) == "function") {
                        onSuccess(element, treeName, id, data, _self);
                    }
                },
                error: function (request, status) {
                    if (typeof (onError) == "function") {
                        onError(element, treeName, id, request, status, _self);
                    } else {
                        //console.log("Error load tree request!", status);
                    }
                },
                //timeout: _self.settings.timeout,
                dataType: "html"
            };
            $.ajax(ajaxParam);
        }        
    };

    /**
     * Возвращает выбранные элементы
     * @return array
     */
    this.checked = function () {
        var id = [];
        _self.settings.treeElement.find("input[type=checkbox][name='sel[]']").each(function (idx, elem) {
            if (elem.checked) {
                id[id.length] = elem.value;
            }
        });
        return id;
    };

    /**
     * Сбросить все отметки
     */
    this.unchecked = function () {
        _self.settings.treeElement.find("input[type=checkbox]").each(function (i, elem) {
            elem.checked = false;
        });
        $("li.checked").removeClass("checked");
    };

    /** Найти текст и отметить найденные элементы
     * 
     * @param {*} text 
     */
    this.findme = function (text) {
        let $li = _self.settings.treeElement.find('span:contains("' + text + '")').parent("li");
        $li.addClass("checked");
        $li.children("input[type=checkbox]").each(function (i, elem) {
            elem.checked = true;
        });
    };

    /** Найти текст и снять отметки на найденных элементах
     * 
     * @param {*} text 
     */
    this.unfindme = function (text) {
        let $li = _self.settings.treeElement.find('span:contains("' + text + '")').parent("li");
        $li.removeClass("checked");
        $li.children("input[type=checkbox]").each(function (i, elem) {
            elem.checked = false;
        });
    };

    /**
     * Установить element, как выделенный
     * allowSelect разрешает операцию
     * @param dom element
     * @return void
     */
    this.select = function (element) {
        var e = null;
        if (element instanceof jQuery) {
            if (element.length > 0) {
                e = element[0];
            }     
        } else {
            e = element;
        }
        if (_self.settings.allowSelect && _self.selected != e) {
            if (_self.selected) {
                $(_self.selected).removeClass("selected");
            }
            _self.prev_selected = _self.selected;
            _self.selected = e;
            $(e).addClass("selected");
        }
    };

    /**
     * Убрать выделенный элемент
     * @return void
     */
    this.unselect = function () {
        if (_self.settings.allowSelect && _self.selected) {
            $(_self.selected).removeClass("selected")
            _self.prev_selected = _self.selected
            _self.selected = null;
        }        
    };

    this.collapse = function () {
        this.settings.treeElement.find(".subitems").css("display", "none");
    };

    /**
     * Обновить дерево на экране
     * @param func|null onSuccessLoad
     * @return void
     */
    this.refresh = function (onSuccessLoad = null) {
        var id = [];
        var $notEmpty = _self.settings.treeElement.find(".subitems").filter(function (idx) {
            if (this.innerHTML != "") {
                var $item = $(this).siblings(".item");
                if ($item.length > 0) {
                    id[id.length] = $item[0].dataset.id;
                }
                return true;
            }
            return false;
        });
        //$notEmpty.empty();

        // ищем начало дерева
        var $start_node = _self.settings.treeElement.children("ul");
        if ($start_node.length > 0) {
            var start_node = $start_node[0];
            _self.load(start_node, _self.settings.treeName, start_node.dataset.id, onSuccessLoad)
        } else {
            //console.log("Refresh error! Not found start node!");
        }
    };

    /**
     * Удалить элементы дерева, выделенные checkbox
     * Параметр urlDelete определяет контроллера для выполнения операции.
     * Если urlDelete не задано - операция запрещена.
     * @return void
     */
    this.delete = function () {
        if (_self.settings.urlDelete != "") {
            // проверяем по дереву выделенные checkbox
            var id = _self.checked();            
            if (id.length > 0) {
                var ajaxParam = {
                    url: _self.settings.urlDelete,
                    data: {
                        "treeName": _self.settings.treeName,
                        "id[]": id,
                    },
                    //processData: false,
                    success: function (data) {
                        _self.refresh();
                    },
                    error: function (request, status) {
                        //console.log("Error delete request!", status);
                    },
                    //timeout: _self.settings.timeout,
                    dataType: "html"
                };
                $.ajax(ajaxParam);
            }
        }
    };

    /**
     * Изменить родительский узел у отмеченных элементов на выделенный
     * Параметр urlMove определяет контроллера для выполнения операции.
     * Если urlMove не задано - операция запрещена.
     * @return void
     */
    this.move = function () {
        if (_self.settings.urlMove != "" && _self.selected) {
            var id = _self.checked();
            if (id.length > 0) {
                if (typeof (_self.selected.dataset.id) != "undefined") {
                    var selected_id = _self.selected.dataset.id;
                    var ajaxParam = {
                        url: _self.settings.urlMove,
                        data: {
                            "treeName": _self.settings.treeName,
                            "id[]": id,
                            "parentId": selected_id
                        },
                        //processData: false,
                        success: function (data) {
                            _self.refresh(function () {
                                _self.open(selected_id, function () {
									_self.select(_self.byID(selected_id));
                                    //_self.select(_self.selected);
                                });
                            });
                        },
                        error: function (request, status) {
                            //console.log("Error move request!", status);
                        },
                        //timeout: _self.settings.timeout,
                        dataType: "html"
                    };
                    $.ajax(ajaxParam);    
                }
            }
        }
    };

    /** По id узла дерева, возвращает dom элемент
     * 
     * @param integer id 
     */
    this.byID = function (id) {
        //console.log("byID", id, _self.settings.treeElement);
        return _self.settings.treeElement.find(_self.settings.itemSelector + "[data-id=" + id + "]");
    };

    /** Для элемента id ищет контейнеры с подъэлементами
     * Возвращает dom-элемент в котором должны быть подъэлементы.
     * Или null, если подгрузка подъэлементов не задана в шаблоне генерации элементов.
     * 
     * @param {*} id 
     */
    this.subitemsById = function (id) {
        let $element = _self.byID(id);
        let $sim = $element.parentsUntil("li");
        if ($sim.length == 0) {
            $sim = $element.parent();
        } else {
            $sim = $sim.parent();
        }
        let $subitems = $sim.children(_self.settings.subitemSelector);
        if ($subitems.length > 0) {
            return $subitems[0];
        }
        return null;
    };

    /** Выбирает элемент с номером id. Он должен быть загружен.
     * 
     * @param {*} id 
     * @param {*} onAfter 
     */
    this.click = function (id, onAfter = null, allowExpand = true, allowCollapse = true) {
        //console.log('click', id, onAfter);
        let si = _self.subitemsById(id);
        if (si) {
            if (si.innerHTML == "") {
                _self.load(si, _self.settings.treeName, id, onAfter, null);
            } else {         
                if (si.style.display == "none" || si.style.display == "") {
                    if (allowExpand) {
                        si.style.display = "block";
                    }                    
                } else {
                    if (allowCollapse) {
                        si.style.display = "none";
                    }
                }
                if (typeof (onAfter) == "function") {
                    onAfter(id, _self);
                }
            }
        }
    };
    
    /** Раскрывает цепочку по порядку
     * 
     * @param array ids 
     */
    this.clicks = function (ids, onAfter = null, allowExpand = true, allowCollapse = true) {
        //console.log("Cliks!", ids, onAfter);
        if (Array.isArray(ids) && ids.length>0) {
            if (ids.length > 1) {
                _self.click(ids[0], function () {
                    _self.clicks(ids.slice(1), onAfter);
                    if (typeof (onAfter) == "function") {
                        onAfter(ids[0], _self);
                    }
                }, allowExpand, allowCollapse);
            } else {
                _self.click(ids[0], function () {
                    if (typeof (onAfter) == "function") {
                        onAfter(ids[0], _self);
                    }
                }, allowExpand, allowCollapse);
            }
        }
    };

    /** Выбирает элемент, получает всю цепочку и раскрывает ветви дерева
     * 
     * @param {*} id 
     */
    this.choose = function (id) {
        if (_self.settings.urlBranch != "") {
            var ajaxParam = {
                url: _self.settings.urlBranch,
                data: {
                    "treeName": _self.settings.treeName,
                    "id": id,
                },
                //processData: false,
                success: function (data) {
                    _self.settings.treeElement.find(_self.settings.itemSelector).removeClass("choose");
                    _self.clicks(data, function (id, self) {
                        self.byID(id).addClass("choose");
                    }, true, false);
                },
                error: function (request, status) {
                    //console.log("Error request!", status);
                },
                //timeout: _self.settings.timeout,
                dataType: "json"
            };
            $.ajax(ajaxParam);
        }
    };

    /** Отметить в дереве элементы по их id. в работу берутся все checkbox и radio
     * 
     * @param array arID 
     */
    this.checkIt = function (arID) {
        //console.log("checked", arID);

        for (let i = 0; i < arID.length; i++) {
            //_self.byID(arID[i])
            let input = _self.settings.treeElement.find("input[value=" + arID[i] + "]");
            /*if (input.length > 0) {
                input.each(function (idx, elem) {
                    elem.checked = true;
                });
            } else */{
                if (_self.settings.urlBranch != "") {
                    var ajaxParam = {
                        url: _self.settings.urlBranch,
                        data: {
                            "treeName": _self.settings.treeName,
                            "id": arID[i],
                        },
                        //processData: false,
                        success: function (data) {
                            _self.clicks(data, function (id, self) {
                                if (id == arID[i]) {
                                    let input = self.settings.treeElement.find("input[value=" + arID[i] + "]");
                                    if (input.length > 0) {
                                        input.each(function (idx, elem) {
                                            elem.checked = true;
                                        });
                                    }
                                }
                            }, true, false);
                        },
                        error: function (request, status) {
                            //console.log("Error request!", status);
                        },
                        //timeout: _self.settings.timeout,
                        dataType: "json"
                    };
                    $.ajax(ajaxParam);
                }    
            }
        }
    };

    this.open = function (id, callback = null) {
        if (_self.settings.urlBranch != "") {
            var ajaxParam = {
                url: _self.settings.urlBranch,
                data: {
                    "treeName": _self.settings.treeName,
                    "id": id,
                },
                //processData: false,
                success: function (data) {
                    _self.clicks(data, callback);
                },
                error: function (request, status) {
                    //console.log("Error request!", status);
                },
                //timeout: _self.settings.timeout,
                dataType: "json"
            };
            $.ajax(ajaxParam);
        }
    };

    /**
     * Инициализация
     * @return void
     */
    this.init = function () {
        var $element = $(_self.settings.treeElement);
        _self.settings.treeElement.on("click", _self.settings.expandSelector, function (e) {
            var element = this;
            _self.click(element.dataset.id);
        });
        if (_self.settings.allowSelect) {
            _self.settings.treeElement.on("contextmenu", _self.settings.itemSelector, function (e) {
                var element = this;
                if (element == _self.selected) {
                    _self.unselect();
                } else {
                    _self.select(element);
                }
                e.stopPropagation();
                //e.stopImmediatePropagation();
                return false;
            });
        }
        _self.settings.treeElement.on("dblclick", _self.settings.itemSelector, function (e) {
            return false;
        });
    };
/**********************************************/
    this.init();
}; // end classTree