$(function(){
  $(".sudoku-hole").click(function(){
    var allCells = $(".sudoku-cell");
    allCells.removeClass('pulse');
    allCells.removeClass('btn');
    $(this).addClass('pulse');
    $(this).addClass('btn');
  });
});
