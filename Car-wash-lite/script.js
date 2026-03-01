// Scroll reveal
if('IntersectionObserver' in window){
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if(e.isIntersecting){e.target.classList.add('on');io.unobserve(e.target)}
    });
  },{threshold:0.1,rootMargin:'0px 0px -30px 0px'});
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
}

// Highlight today's hours
(function(){
  const day = new Date().getDay();
  const rows = document.querySelectorAll('.hours-row');
  const map = {1:0,2:0,3:0,4:0,5:0,6:1,0:2};
  if(rows.length && rows[map[day]]){
    rows.forEach(r => r.classList.remove('today'));
    rows[map[day]].classList.add('today');
  }
})();
