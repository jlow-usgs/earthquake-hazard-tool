'use strict';
var CollectionSelectBox = require('mvc/CollectionSelectBox'),
    SelectedCollectionView = require('mvc/SelectedCollectionView'),
    Util = require('util/Util');

var ContourTypeView = function (params) {
  var _this,
      _initialize,

      _contourType,

      _updateContourType;

  _this = SelectedCollectionView(params);

  _initialize = function (params) {
    _contourType = params.contourType;

    _this.el.innerHTML = '<div class="selectBox"></div>' +
      '<div class="message"></div>';

    CollectionSelectBox({
      collection: _contourType,
      el: _this.el.querySelector('.selectBox'),
      includeBlankOption: true,
      format: function (model) {
        return model.get('display');
      }
    });

    // bind to select on contour type change
    _contourType.on('select', _updateContourType, _this);
    _contourType.on('deselect', _updateContourType, _this);

    _this.render();
  };

  /**
   * Update the currently selected analysis model with the currently
   * selected contour type.
   */
  _updateContourType = function () {
    if (_this.model) {
      _this.model.set({'contourType': _contourType.getSelected()});
    }
  };

  /**
   * Calls CollectionSelectionBox.destroy() and cleans up local variables
   */
  _this.destroy = Util.compose(function () {
    _contourType.off('select', _updateContourType, _this);
    _contourType.off('deselect', _updateContourType, _this);
    _updateContourType = null;
    _contourType = null;
    _this = null;
    _initialize = null;
  }, _this.destroy);

  _this.render = function () {
    var contourType;

    _updateContourType();

    if (_this.model && _this.model.get('contourType')) {
      contourType = _this.model.get('contourType').get('display');
      if (contourType === 'Gridded Hazard')  {
        _this.el.querySelector('.message').innerHTML =
            '<p><small>This data is always for the B/C Boundry.</small></p>';
      } else {
        _this.el.querySelector('.message').innerHTML = null;
      }
    }
  };

  _initialize(params);
  params = null;
  return _this;
};

module.exports = ContourTypeView;