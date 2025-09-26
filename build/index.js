(()=>{
  const reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  async function fetchNews(el){
    const count = el.dataset.count || 7;
    const cat   = el.dataset.cat   || '';
    const sticky= el.dataset.sticky || 'off';
    const nonce = el.dataset.nonce || '';
    const root  = (window.wpApiSettings && window.wpApiSettings.root) ? window.wpApiSettings.root : '/wp-json/';
    const res = await fetch(root + 'nt/v1/news?count='+count+'&cat='+encodeURIComponent(cat)+'&sticky='+encodeURIComponent(sticky), {
      headers: { 'X-WP-Nonce': nonce }
    });
    const data = await res.json();
    return data.items || [];
  }

  function prefetch(href){
    if(!href) return;
    try{
      const link = document.createElement('link');
      link.rel = 'prefetch';
      link.href = href;
      document.head.appendChild(link);
    }catch(e){}
  }

  function renderMarquee(container, items, opts){
    const logo = container.dataset.logo || '';
    const urgent = container.dataset.urgent || '';
    const dir  = opts.direction;
    const track = container.querySelector('.nt-items');
    const sep = logo ? '<img class="nt-sep" alt="" src="'+logo+'">' : '<span class="nt-sep">•</span>';
    const html = items.map(i => {
      const urgentBadge = i.sticky && urgent ? '<span class="nt-urgent">'+urgent+'</span> ' : '';
      return '<a class="nt-link" href="'+i.url+'">'+urgentBadge+i.title+'</a>';
    }).join(sep);
    track.innerHTML = '<div class="nt-marquee '+dir+'"><div class="nt-row">'+html+'</div><div class="nt-row">'+html+'</div></div>';
    const spd = Math.max(5, Math.min(120, Math.floor((items.length*opts.speed)/1000)));
    track.querySelector('.nt-marquee').style.setProperty('--spd', spd+'s');

    // Pause on hover/focus
    const wrap = container.querySelector('.nt-marquee');
    ['mouseenter','focusin'].forEach(ev => wrap.addEventListener(ev, ()=>wrap.classList.add('paused')));
    ['mouseleave','focusout','blur'].forEach(ev => wrap.addEventListener(ev, ()=>wrap.classList.remove('paused')));
  }

  function renderTypewriter(container, items, opts){
    const track = container.querySelector('.nt-items');
    const urgent = container.dataset.urgent || '';
    let idx=0, paused=false, timer=null;

    function bindPause(node){
      ['mouseenter','focusin'].forEach(ev => node.addEventListener(ev, ()=>{ paused=true; }));
      ['mouseleave','focusout','blur'].forEach(ev => node.addEventListener(ev, ()=>{ paused=false; }));
    }

    function show(){
      const item = items[idx];
      const txt = (item.sticky && urgent ? urgent+' - ' : '') + item.title;
      let i=0;
      track.innerHTML = '<a class="nt-type" href="'+item.url+'"></a>';
      const node = track.querySelector('.nt-type');
      bindPause(node);

      const interval = Math.max(10, Math.min(60, Math.floor(opts.speed / Math.max(20, txt.length))));

      clearInterval(timer);
      timer = setInterval(()=>{
        if(paused) return;
        if(i<=txt.length){ node.textContent = txt.slice(0,i++); }
        else { clearInterval(timer); if(!paused){ setTimeout(next, Math.max(400, Math.floor(opts.speed/2))); } }
      }, interval);

      // Prefetch next
      const nextIndex = (idx+1)%items.length;
      prefetch(items[nextIndex]?.url);
    }
    function next(){ idx=(idx+1)%items.length; show(); }
    function prev(){ idx=(idx-1+items.length)%items.length; show(); }

    container.querySelector('.nt-next') && container.querySelector('.nt-next').addEventListener('click', prev);
    container.querySelector('.nt-prev') && container.querySelector('.nt-prev').addEventListener('click', next);

    // Keyboard support (left/right)
    container.addEventListener('keydown', (e)=>{
      if(e.key === 'ArrowLeft'){ prev(); }
      if(e.key === 'ArrowRight'){ next(); }
    });

    show();
  }

  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.nt-ticker').forEach(async (root)=>{
      const opts = {
        speed: parseInt(root.dataset.speed || '5000', 10),
        direction: root.dataset.direction || (document.dir==='rtl'?'rtl':'ltr'),
        mode: root.dataset.mode || 'marquee'
      };
      const items = await fetchNews(root);
      if(!items.length){ root.querySelector('.nt-items').innerHTML = '<em>لا توجد أخبار.</em>'; return; }
      if(reduced){
        root.querySelector('.nt-items').innerHTML = '<a class="nt-link" href="'+items[0].url+'">'+(items[0].sticky ? (root.dataset.urgent||'')+' - ' : '')+items[0].title+'</a>';
        return;
      }
      if(opts.mode === 'typewriter') renderTypewriter(root, items, opts);
      else renderMarquee(root, items, opts);
    });
  });
})();