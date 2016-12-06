//Support fuer Safari und andere iOs Browsers
function hasHtml5Validation () {
  return typeof document.createElement('input').checkValidity === 'function';
}

if (hasHtml5Validation()) {
  $('.validate-form').submit(function (e) {
    if (!this.checkValidity()) {
      e.preventDefault();
      $(this).addClass('invalid');
      $('#status').html('invalid');
    } else {
      $(this).removeClass('invalid');
      $('#status').html('submitted');
    }
  });
}
//math to text
function makenumber(numb){
if(numb==1)return "Eins";
if(numb==2)return "Zwei";
if(numb==3)return "Drei";
if(numb==4)return "Vier";
if(numb==5)return "Fünf";
if(numb==6)return "Sechs";
if(numb==7)return "Sieben";
if(numb==8)return "Acht";
if(numb==9)return "Neun";
if(numb==10)return "Zehn";
}//end makenumber function
function placenumber(){
var x = Math.floor((Math.random() * 10) + 1);
var y = Math.floor((Math.random() * 10) + 1);
var no1 = makenumber(x);
var no2 = makenumber(y);
var ans = x+y;
document.getElementById('Antwort').pattern=ans;
document.getElementById("no1").innerHTML = no1;
document.getElementById("no2").innerHTML = no2;
}//end placenumber function