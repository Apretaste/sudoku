$(function(){
  $(".sudoku-hole").click(function(){
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

    $(".row-" + row).addClass('row-selected');
    $(".col-" + col).addClass('col-selected');
    $(".square-" + sq).addClass('square-selected');
  });
});
