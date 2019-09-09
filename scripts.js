
function showToast(text) {
  M.toast({
    html: text
  });
}

var sudokuGame = {
  userSolution: [],
  selected: null
};

$(function(){
  $(".sudoku-hole").click(function(){

    sudokuGame.selected = $(this).attr('id');

    var allCells = $(".sudoku-cell");
    var row = $(this).attr('data-row');
    var col = $(this).attr('data-col');
    var sq = $(this).attr('data-square');

    allCells.removeClass('blink_me');
    //allCells.removeClass('btn');
    allCells.removeClass('row-selected');
    allCells.removeClass('col-selected');
    allCells.removeClass('square-selected');

    $(this).addClass('blink_me');
    //$(this).addClass('btn');

    var eRow = $(".row-" + row);
    var eCol = $(".col-" + col);
    var eSq = $(".square-" + sq);
    eRow.addClass('row-selected');
    eCol.addClass('col-selected');
    eSq.addClass('square-selected');

    eRow.removeClass('red lighten-3');
    eCol.removeClass('red lighten-3');
    eSq.removeClass('red lighten-3');
  });

  $(".keyboard").click(function(){
    if (sudokuGame.selected !== null) {
      var cell = $('#' + sudokuGame.selected);
      cell.html($(this).attr('data-value'));
      sudokuGame.userSolution[cell.attr('data-i')] = Number($(this).attr('data-value'));
    }
  });
});

function validate(){

  var allCells = $(".sudoku-cell");
  allCells.removeClass('red lighten-3');
  allCells.removeClass('blink_me');
  allCells.removeClass('row-selected');
  allCells.removeClass('col-selected');
  allCells.removeClass('square-selected');

  var valid = true;
  for (var i =0; i<81;i++) {
    var v = -1;
    if (sudoku[i] === 0) {
      if (typeof sudokuGame.userSolution[i] !== 'undefined') v = sudokuGame.userSolution[i];
      if (original[i] !== v) {
        valid = false;
        $('#sudoku-cell-'+i).addClass('red lighten-3');
      }
    }
  }

  if (valid) {
    showToast('Bien !! Resolviste el Sudoku!');
  } else {
    showToast('Mal !! Algo no est&aacute; bien en tu Sudoku!');
  }

  return valid;
}

