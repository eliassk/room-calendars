(function(){
  document.addEventListener('DOMContentLoaded', function(){
    const container = document.getElementById('rc-calendars');
    if (!container) return;
    let rooms = [];
    try {
      rooms = JSON.parse(container.getAttribute('data-rooms') || '[]');
    } catch(e) {
      return;
    }
    const eventsByRoom = {};
    const colorMap = {};
    rooms.forEach(room => {
      const key = room.name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
      eventsByRoom[key] = [];
      colorMap[key] = room.color;
      const opt = container.querySelector(`.rc-option[data-room="${key}"]`);
      if(opt && !opt.querySelector('.rc-dot')) {
        const dot = document.createElement('span');
        dot.className = 'rc-dot';
        dot.style.backgroundColor = colorMap[key];
        opt.prepend(dot);
      }
    });

    const calendarEl = document.getElementById('rc-calendar-container');
    if (!calendarEl) return;
    const calendar = new FullCalendar.Calendar(calendarEl, {
      locale: 'pl',
      initialView: 'listMonth',
      headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
      height: 'auto',
      contentHeight: 'auto', 
      events: []
    });

    let loaded = 0;
    rooms.forEach(room => {
      const key = room.name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
      fetch(rc_params.ajax_url, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'rc_fetch_ics', url: room.url})
      })
      .then(r=>r.json())
      .then(json=>{
        if(json.success) {
          const comp = new ICAL.Component(ICAL.parse(json.data));
          const vevents = comp.getAllSubcomponents('vevent');
          vevents.forEach(ve=>{
            const e = new ICAL.Event(ve);
            const id = e.uid || e.summary + e.startDate;
            eventsByRoom[key].push({
              id: id,
              title: e.summary,
              start: e.startDate.toString(),
              end: e.endDate.toString(),
              allDay: e.startDate.isDate,
              color: colorMap[key],
              sources: [room.name]
            });
          });
        }
      })
      .finally(()=>{
        loaded++;
        if(loaded === rooms.length) {
          calendar.render();
          updateCalendar();
          bindOptions();
        }
      });
    });

    function bindOptions() {
      container.querySelectorAll('.rc-option').forEach(opt=>{
        opt.addEventListener('click', function(){
          opt.classList.toggle('active');
          updateCalendar();
        });
      });
    }

    function updateCalendar() {
      const merged = {};
      container.querySelectorAll('.rc-option.active').forEach(opt=>{
        const key = opt.getAttribute('data-room');
        (eventsByRoom[key]||[]).forEach(ev=>{
          if(!merged[ev.id]) {
            merged[ev.id] = {...ev};
          } else {
            ev.sources.forEach(src=>{
              if(!merged[ev.id].sources.includes(src)) merged[ev.id].sources.push(src);
            });
          }
        });
      });
      const events = Object.values(merged).map(ev=>({
        ...ev,
        title: ev.title + ' (' + ev.sources.join(', ') + ')'
      }));
      calendar.removeAllEvents();
      calendar.addEventSource(events);
    }
  });
})();