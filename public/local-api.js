/**
 * SAMtrening – Local API
 * Pełna symulacja backendu w localStorage.
 * Działa gdy serwer Node.js jest wyłączony — dane przechowywane w przeglądarce.
 * Dane są wspólne między index.html / admin.html / trainer.html (ten sam origin).
 */
const LocalApi = (() => {
  const K = {
    bookings:'sam_bookings', trainers:'sam_trainers', avail:'sam_availability',
    notes:'sam_client_notes', settings:'sam_settings', blocked:'sam_blocked',
    clientPrices:'sam_client_prices',
    clients:'sam_clients',           // registered client accounts
    messages:'sam_messages',         // chat messages (trainer ↔ client)
    progress:'sam_progress',         // client progress entries
    packages:'sam_packages',         // session packages per client
    payments:'sam_payments',         // payment records per client
    measurements:'sam_measurements', // Tanita body measurements per client
  };

  const DEFAULT_TRAINERS = {
    kasia: {
      id:'kasia', name:'Katarzyna Janecka', nameShort:'Kasia',
      role:'Trenerka kobiet | 13 lat doświadczenia', color:'pink',
      specializations:['Trening kobiet','Ciąża & połóg','Sylwetka','Zdrowie'],
      description:'Specjalistka od treningu kobiet z 13-letnim doświadczeniem. Absolwentka AWF Kraków w specjalności gimnastyki korekcyjnej i trenerka lekkoatletyczna II klasy. Pracuje z kobietami na każdym etapie życia — budowanie sylwetki, ciąża, powrót do formy po porodzie. Jej holistyczne podejście i pełna dyskrecja sprawiają, że klientki osiągają trwałe efekty w atmosferze pełnego zaufania i komfortu.',
      bankAccount:'', blikPhone:'', phone:'', password:'kasia2024',
    },
    maciek: {
      id:'maciek', name:'Maciej Samborski', nameShort:'Maciek',
      role:'Trener przedsiębiorców | b. lekkoatleta MŚ', color:'blue',
      specializations:['Przedsiębiorcy','Siła & dynamika','Bieganie','HIIT'],
      description:'Trener personalny przedsiębiorców, lekarzy i osób medialnych, które muszą wyglądać i czuć się doskonale każdego dnia. Absolwent AWF Kraków, były lekkoatleta z doświadczeniem na Mistrzostwach Świata i Europy. Specjalizuje się w treningu siłowym, dynamicznym i bieganiu. Projektuje treningi dla zabieganych — krótkie, intensywne, dające mierzalne wyniki.',
      bankAccount:'', blikPhone:'', phone:'', password:'maciek2024',
    },
  };

  // ── Storage helpers ──────────────────────────────────────────────────────────
  const get  = (k, def) => { try { return JSON.parse(localStorage.getItem(k)) ?? def; } catch { return def; } };
  const set  = (k, v)   => { try { localStorage.setItem(k, JSON.stringify(v)); } catch {} };
  const getB = ()       => get(K.bookings, []);
  const saveB = b       => set(K.bookings, b);
  const getT = ()       => get(K.trainers, {...DEFAULT_TRAINERS});
  const saveT = t       => set(K.trainers, t);
  const getA = ()       => get(K.avail, {});
  const saveA = a       => set(K.avail, a);

  // ── Server detection ─────────────────────────────────────────────────────────
  // In production (Railway / samtrening.com) we sync to the PostgreSQL backend.
  // On localhost / file:// we work purely in localStorage (offline-first).
  const _IS_PROD = typeof window !== 'undefined' &&
    !['localhost','127.0.0.1',''].includes(window.location.hostname) &&
    window.location.protocol !== 'file:';
  const _APIBASE = ''; // always relative – served from same origin

  async function _apiFetch(method, path, body) {
    const opts = { method, credentials: 'include', headers: { 'Content-Type': 'application/json' } };
    if (body !== undefined) opts.body = JSON.stringify(body);
    return fetch(_APIBASE + path, opts);
  }
  async function _apiJSON(method, path, body) {
    try {
      const res = await _apiFetch(method, path, body);
      if (!res.ok) return null;
      return await res.json();
    } catch(e) { return null; }
  }
  // Fire-and-forget: push write to server, ignore errors
  function _push(method, path, body) {
    if (!_IS_PROD) return;
    _apiFetch(method, path, body).catch(() => {});
  }

  function genId()      { return Date.now().toString(36) + Math.random().toString(36).slice(2,6); }
  function genMeet()    {
    const s = (n) => Array.from({length:n},()=>'abcdefghijklmnopqrstuvwxyz'[Math.floor(Math.random()*26)]).join('');
    return `https://meet.google.com/${s(3)}-${s(4)}-${s(3)}`;
  }
  function getBookingPrice(booking) {
    // Booking's own saved price takes priority (set at time of booking/update)
    if (booking.price && booking.price > 0) return booking.price;
    // Individual client price (per trainer)
    const cp = get(K.clientPrices, {});
    const trainerPrices = cp[booking.trainerId] || {};
    if (booking.phone && trainerPrices[booking.phone]) return trainerPrices[booking.phone];
    // Global default price
    return (get(K.settings, { sessionPrice:200 })).sessionPrice || 200;
  }

  function calcFin(bookings, trainerId, period) {
    const now = new Date();
    const tod = now.toISOString().split('T')[0];
    const b = bookings.filter(x => {
      // Count confirmed (upcoming/today) AND completed (explicitly done) bookings
      if (x.status !== 'confirmed' && x.status !== 'completed') return false;
      if (trainerId && x.trainerId !== trainerId) return false;
      const d = new Date(x.date + 'T12:00:00');
      if (period === 'day')     return x.date === tod;
      if (period === 'week') {
        const ws = new Date(now); ws.setDate(now.getDate() - ((now.getDay()+6)%7)); ws.setHours(0,0,0,0);
        const we = new Date(ws); we.setDate(ws.getDate()+6); we.setHours(23,59,59);
        return d >= ws && d <= we;
      }
      if (period === 'month')   return d.getMonth()===now.getMonth() && d.getFullYear()===now.getFullYear();
      if (period === 'quarter') { const q=Math.floor(now.getMonth()/3); return Math.floor(d.getMonth()/3)===q && d.getFullYear()===now.getFullYear(); }
      if (period === 'year')    return d.getFullYear()===now.getFullYear();
      return true;
    });
    const pp = b.filter(x=>x.paymentChoice==='prepaid');
    const onsite = b.filter(x=>x.paymentChoice!=='prepaid');
    const revenue = b.reduce((s,x)=>s+getBookingPrice(x), 0);
    const prepaidRevenue = pp.reduce((s,x)=>s+getBookingPrice(x), 0);
    const onsiteRevenue  = onsite.reduce((s,x)=>s+getBookingPrice(x), 0);
    return { count:b.length, revenue, prepaidCount:pp.length, prepaidRevenue,
      onsiteCount:onsite.length, onsiteRevenue,
      online:b.filter(x=>x.trainingType==='online').length, inperson:b.filter(x=>x.trainingType==='inperson').length };
  }

  return {
    // ── Trainers ────────────────────────────────────────────────────────────────
    getTrainersList() {
      return Object.values(getT()).map(t => ({
        id:t.id, name:t.name, nameShort:t.nameShort, role:t.role||'Trener Personalny',
        specializations:t.specializations||[], description:t.description||'',
        bankAccount:t.bankAccount||'', blikPhone:t.blikPhone||'',
      }));
    },
    getTrainerById(id) { return getT()[id] || null; },
    verifyTrainer(id, pw) {
      const t = getT()[id]; if (!t) return null;
      return (pw === (t.password || id+'2024')) ? {...t, password:undefined} : null;
    },
    updateTrainer(id, updates) {
      const ts = getT(); if (!ts[id]) return null;
      Object.assign(ts[id], updates); saveT(ts);
      _push('PATCH', '/api/trainer/profile', updates);
      return {...ts[id], password:undefined};
    },

    // ── Availability ────────────────────────────────────────────────────────────
    getAvailForDate(trainerId, date) {
      const booked  = getB().filter(b=>b.trainerId===trainerId&&b.date===date&&b.status!=='cancelled').map(b=>b.time);
      const avail   = (getA()[trainerId]||{})[date]||[];
      return { booked, available: avail };
    },
    getTrainerAvail(trainerId) { return (getA()[trainerId])||{}; },
    saveAvailDay(trainerId, date, slots) {
      const a=getA(); if(!a[trainerId]) a[trainerId]={};
      a[trainerId][date]=[...new Set(slots)].sort(); saveA(a);
      _push('POST', '/api/trainer/availability', {date, slots});
      return {success:true};
    },
    saveAvailBulk(trainerId, dates) {
      const a=getA(); if(!a[trainerId]) a[trainerId]={};
      Object.entries(dates).forEach(([d,s])=>{ a[trainerId][d]=[...new Set(s||[])].sort(); });
      saveA(a); return {success:true};
    },

    // ── Client booking ──────────────────────────────────────────────────────────
    createClientBooking(payload) {
      const bs=getB();
      if (bs.find(b=>b.trainerId===payload.trainerId&&b.date===payload.date&&b.time===payload.time&&b.status!=='cancelled'))
        return {error:'Ten termin jest już zajęty.'};
      const nW=payload.recurring?(parseInt(payload.recurringWeeks)||1):1;
      const online=payload.trainingType==='online';
      const base={
        id:genId(), trainer:payload.trainer, trainerId:payload.trainerId,
        clientId:payload.clientId||null,
        date:payload.date, time:payload.time, name:payload.name, surname:payload.surname,
        phone:payload.phone, email:payload.email||'',
        trainingType:online?'online':'inperson', clientMessage:payload.clientMessage||'',
        language:payload.language||'pl', recurring:!!payload.recurring, recurringWeeks:nW,
        paymentChoice:payload.paymentChoice||'onsite',
        paymentStatus:payload.paymentChoice==='prepaid'?'prepaid':'pending',
        meetLink:online?genMeet():null, createdAt:new Date().toISOString(),
        status:'pending', smsHistory:[], adminNotes:'', bookedBy:'client', recurringGroupId:null, _local:true,
      };
      const allNew=[base];
      for(let w=1;w<nW;w++){
        const nd=new Date(payload.date+'T12:00:00'); nd.setDate(nd.getDate()+7*w);
        const nds=nd.toISOString().split('T')[0];
        if(!bs.find(b=>b.trainerId===payload.trainerId&&b.date===nds&&b.time===payload.time&&b.status!=='cancelled'))
          allNew.push({...base,id:genId(),date:nds,meetLink:online?genMeet():null,recurringGroupId:base.id,createdAt:new Date().toISOString()});
      }
      allNew.forEach(b=>bs.push(b)); saveB(bs);
      // Sync to server
      allNew.forEach(b => _push('POST', '/api/client/bookings', {...b, clientName:b.name, clientSurname:b.surname, clientPhone:b.phone, clientEmail:b.email}));
      return {success:true, bookingId:base.id, totalBookings:allNew.length, _local:true};
    },

    // ── Admin booking ───────────────────────────────────────────────────────────
    createAdminBooking(payload) {
      const bs=getB();
      if (bs.find(b=>b.trainerId===payload.trainerId&&b.date===payload.date&&b.time===payload.time&&b.status!=='cancelled'))
        return {error:'Ten termin jest już zajęty.'};
      const online=payload.trainingType==='online';
      const b={
        id:genId(), trainer:payload.trainer, trainerId:payload.trainerId,
        date:payload.date, time:payload.time, name:payload.name, surname:payload.surname,
        phone:payload.phone, email:payload.email||'',
        trainingType:online?'online':'inperson', clientMessage:payload.clientMessage||'',
        language:payload.language||'pl', recurring:false, recurringWeeks:1,
        paymentChoice:payload.paymentChoice||'onsite',
        paymentStatus:payload.paymentChoice==='prepaid'?'prepaid':'pending',
        meetLink:online?genMeet():null, createdAt:new Date().toISOString(),
        status:'confirmed', smsHistory:[], adminNotes:'Rezerwacja dodana przez administratora', bookedBy:'admin', _local:true,
      };
      bs.push(b); saveB(bs);
      _push('POST', '/api/trainer/bookings', {...b, bookedBy:'admin', status:'confirmed'});
      return {success:true, bookingId:b.id, booking:b, _local:true};
    },

    // ── Trainer booking for client ──────────────────────────────────────────────
    createTrainerBooking(trainerId, trainerName, payload) {
      const bs=getB(); const created=[],conflicts=[];
      (payload.slots||[]).forEach(({date,time})=>{
        if(bs.find(b=>b.trainerId===trainerId&&b.date===date&&b.time===time&&b.status!=='cancelled')){conflicts.push({date,time});return;}
        const online=payload.trainingType==='online';
        const b={
          id:genId(), trainer:trainerName, trainerId, date, time,
          name:payload.name, surname:payload.surname, phone:payload.phone, email:payload.email||'',
          trainingType:online?'online':'inperson', clientMessage:payload.clientMessage||'',
          language:payload.language||'pl', recurring:false, recurringWeeks:1,
          paymentChoice:payload.paymentChoice||'onsite',
          paymentStatus:payload.paymentChoice==='prepaid'?'prepaid':'pending',
          meetLink:online?genMeet():null, createdAt:new Date().toISOString(),
          status:'confirmed', smsHistory:[], adminNotes:`Rezerwacja przez trenera`, bookedBy:'trainer', _local:true,
        };
        bs.push(b); created.push(b);
      });
      saveB(bs);
      created.forEach(b => _push('POST', '/api/trainer/bookings', b));
      return {success:true, created:created.length, conflicts:conflicts.length, bookings:created};
    },

    // ── Read bookings ───────────────────────────────────────────────────────────
    getAdminBookings(trainerId, status) {
      let b=getB().slice().reverse();
      if(trainerId) b=b.filter(x=>x.trainerId===trainerId);
      if(status)    b=b.filter(x=>x.status===status);
      return b;
    },
    getTrainerBookings(trainerId, status) {
      let b=getB().filter(x=>x.trainerId===trainerId).slice().reverse();
      if(status) b=b.filter(x=>x.status===status);
      return b;
    },
    patchBooking(id, trainerId, updates) {
      const bs=getB();
      const idx=trainerId ? bs.findIndex(b=>b.id===id&&b.trainerId===trainerId) : bs.findIndex(b=>b.id===id);
      if(idx===-1) return null;
      Object.assign(bs[idx], updates); saveB(bs);
      _push('PATCH', `/api/trainer/booking/${id}`, updates);
      return bs[idx];
    },
    saveSmsHistory(bookingId, entry) {
      const bs=getB();
      const idx=bs.findIndex(b=>b.id===bookingId);
      if(idx===-1) return null;
      bs[idx].smsHistory=bs[idx].smsHistory||[];
      bs[idx].smsHistory.push({...entry, sentAt:entry.sentAt||new Date().toISOString(), _local:true});
      saveB(bs);
      return bs[idx];
    },
    saveEmailHistory(bookingId, entry) {
      const bs=getB();
      const idx=bs.findIndex(b=>b.id===bookingId);
      if(idx===-1) return null;
      bs[idx].emailHistory=bs[idx].emailHistory||[];
      bs[idx].emailHistory.push({...entry, sentAt:entry.sentAt||new Date().toISOString(), _local:true});
      saveB(bs);
      return bs[idx];
    },

    // ── Client Prices ─────────────────────────────────────────────────────────────
    // Structure: { trainerId: { phone: price } }
    getClientPrices(trainerId) {
      return (get(K.clientPrices, {}))[trainerId] || {};
    },
    getClientPrice(trainerId, phone) {
      const defaultPrice = (get(K.settings, { sessionPrice:200 })).sessionPrice || 200;
      return ((get(K.clientPrices, {}))[trainerId]||{})[phone] || defaultPrice;
    },
    saveClientPrice(trainerId, phone, price) {
      const cp = get(K.clientPrices, {});
      if (!cp[trainerId]) cp[trainerId] = {};
      if (!price || price <= 0) { delete cp[trainerId][phone]; }
      else { cp[trainerId][phone] = parseInt(price); }
      set(K.clientPrices, cp);
      _push('POST', '/api/trainer/prices', { phone, price: price||0 });
      return { success:true };
    },
    // Also expose getBookingPrice for use in HTML templates
    getBookingPrice(booking) { return getBookingPrice(booking); },

    // ── Client Notes ─────────────────────────────────────────────────────────────
    // Structure: { trainerId: { phone: 'note text', ... } }
    getClientNotes(trainerId) {
      const notes = get(K.notes, {});
      return notes[trainerId] || {};
    },
    getClientNote(trainerId, phone) {
      const notes = get(K.notes, {});
      return (notes[trainerId]||{})[phone] || '';
    },
    saveClientNote(trainerId, phone, note) {
      const notes = get(K.notes, {});
      if (!notes[trainerId]) notes[trainerId] = {};
      notes[trainerId][phone] = note;
      set(K.notes, notes);
      _push('POST', '/api/trainer/notes', { phone, note });
      return { success: true };
    },

    // ── Settings (prices, config) ────────────────────────────────────────────────
    getSettings() {
      return get(K.settings, { sessionPrice:200, currency:'PLN', autoReminder:false, reminderHours:24 });
    },
    saveSettings(updates) {
      const s = get(K.settings, { sessionPrice:200, currency:'PLN', autoReminder:false, reminderHours:24 });
      Object.assign(s, updates);
      set(K.settings, s);
      _push('POST', '/api/trainer/settings', s);
      return s;
    },

    // ── Blocked slots ─────────────────────────────────────────────────────────────
    // Structure: { trainerId: { 'YYYY-MM-DD': ['HH:MM', ...] } }
    getBlockedSlots(trainerId) {
      const bl = get(K.blocked, {});
      return bl[trainerId] || {};
    },
    saveBlockedDay(trainerId, date, slots) {
      const bl = get(K.blocked, {});
      if (!bl[trainerId]) bl[trainerId] = {};
      if (!slots || slots.length===0) { delete bl[trainerId][date]; }
      else { bl[trainerId][date] = [...new Set(slots)].sort(); }
      set(K.blocked, bl);
      return { success:true };
    },
    isSlotBlocked(trainerId, date, time) {
      const bl = get(K.blocked, {});
      return !!((bl[trainerId]||{})[date]||[]).includes(time);
    },

    // ── Weekly trend (for charts) ────────────────────────────────────────────────
    getWeeklyTrend(trainerId, n=8) {
      const bs = getB().filter(b => (b.status==='confirmed'||b.status==='completed') && (trainerId==='all' || !trainerId || b.trainerId===trainerId));
      const weeks = [];
      const now = new Date();
      for (let i=n-1; i>=0; i--) {
        const d = new Date(now);
        d.setDate(d.getDate() - i*7);
        const ws = new Date(d);
        ws.setDate(d.getDate() - ((d.getDay()+6)%7)); ws.setHours(0,0,0,0);
        const we = new Date(ws); we.setDate(ws.getDate()+7); we.setHours(0,0,0,0);
        const wb = bs.filter(b=>{ const bd=new Date(b.date+'T12:00:00'); return bd>=ws && bd<we; });
        weeks.push({
          label:`${String(ws.getDate()).padStart(2,'0')}.${String(ws.getMonth()+1).padStart(2,'0')}`,
          count: wb.length,
          revenue: wb.reduce((s,b)=>s+getBookingPrice(b),0),
          prepaid: wb.filter(b=>b.paymentChoice==='prepaid').length,
        });
      }
      return weeks;
    },
    getMonthlyTrend(trainerId, n=6) {
      const bs = getB().filter(b => (b.status==='confirmed'||b.status==='completed') && (trainerId==='all' || !trainerId || b.trainerId===trainerId));
      const months = [];
      const now = new Date();
      const mNames = ['Sty','Lut','Mar','Kwi','Maj','Cze','Lip','Sie','Wrz','Paź','Lis','Gru'];
      for (let i=n-1; i>=0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth()-i, 1);
        const mb = bs.filter(b=>{ const bd=new Date(b.date+'T12:00:00'); return bd.getMonth()===d.getMonth() && bd.getFullYear()===d.getFullYear(); });
        months.push({ label:`${mNames[d.getMonth()]} ${d.getFullYear()}`, count:mb.length, revenue:mb.reduce((s,b)=>s+getBookingPrice(b),0) });
      }
      return months;
    },

    // ── Stats / Financial ───────────────────────────────────────────────────────
    getStats() {
      const b=getB();
      return { total:b.length, pending:b.filter(x=>x.status==='pending').length,
        confirmed:b.filter(x=>x.status==='confirmed').length, cancelled:b.filter(x=>x.status==='cancelled').length,
        kasia:b.filter(x=>x.trainerId==='kasia'&&x.status!=='cancelled').length,
        maciek:b.filter(x=>x.trainerId==='maciek'&&x.status!=='cancelled').length };
    },
    getFinancial(trainerId) {
      const bs=getB(); const periods=['day','week','month','quarter','year'];
      const r={}; periods.forEach(p=>{r[p]=calcFin(bs,trainerId||null,p);}); return r;
    },
    getAdminFinancial() {
      const bs=getB(); const periods=['day','week','month','quarter','year'];
      const overall={}; periods.forEach(p=>{overall[p]=calcFin(bs,null,p);});
      const byTrainer={};
      Object.keys(getT()).forEach(tid=>{
        byTrainer[tid]={}; periods.forEach(p=>{byTrainer[tid][p]=calcFin(bs,tid,p);});
      });
      return {overall, byTrainer};
    },

    // ══════════════════════════════════════════════════════════════════════════════
    // ── CLIENT ACCOUNTS ──────────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════════
    // Client structure: { id, name, surname, email, phone, password,
    //   trainerId, status:'active'|'inactive', createdAt, lastLogin, avatar }

    getClients() { return get(K.clients, []); },
    getClientByEmail(email) {
      return get(K.clients, []).find(c => c.email.toLowerCase() === email.toLowerCase()) || null;
    },
    getClientById(id) { return get(K.clients, []).find(c => c.id === id) || null; },
    getClientsByTrainer(trainerId) {
      return get(K.clients, []).filter(c => c.trainerId === trainerId);
    },

    // Admin registers a new client
    registerClient(payload) {
      const existing = get(K.clients, []);
      if (existing.find(c => c.email.toLowerCase() === payload.email.toLowerCase()))
        return { error: 'Klient z tym adresem email już istnieje.' };
      const client = {
        id: genId(),
        name: payload.name, surname: payload.surname,
        email: payload.email.toLowerCase().trim(),
        phone: payload.phone || '',
        password: payload.password || (payload.name.toLowerCase() + '2024'),
        trainerId: payload.trainerId || null,
        goal: payload.goal || '',
        notes: payload.notes || '',
        status: 'active',
        createdAt: new Date().toISOString(),
        lastLogin: null,
        emailVerified: true, // admin-registered → auto verified
      };
      existing.push(client);
      set(K.clients, existing);
      _push('POST', '/api/trainer/clients', client);
      return { success: true, client: {...client, password: undefined} };
    },

    // Update client profile (admin or client themselves)
    updateClient(id, updates) {
      const clients = get(K.clients, []);
      const idx = clients.findIndex(c => c.id === id);
      if (idx === -1) return null;
      Object.assign(clients[idx], updates);
      set(K.clients, clients);
      _push('PATCH', `/api/trainer/client/${id}`, updates);
      return {...clients[idx], password: undefined};
    },

    // Client login
    verifyClient(email, password) {
      const c = get(K.clients, []).find(c => c.email.toLowerCase() === email.toLowerCase());
      if (!c) return null;
      if (c.password !== password) return null;
      // Update lastLogin
      const clients = get(K.clients, []);
      const idx = clients.findIndex(x => x.id === c.id);
      if (idx !== -1) { clients[idx].lastLogin = new Date().toISOString(); set(K.clients, clients); }
      return {...c, password: undefined};
    },

    // ── MESSAGES (chat trainer ↔ client) ──────────────────────────────────────────
    // Message: { id, from:'trainer'|'client', fromId, toId, text, sentAt, read }
    getMessages(trainerId, clientId) {
      return get(K.messages, [])
        .filter(m => (m.fromId===trainerId&&m.toId===clientId) || (m.fromId===clientId&&m.toId===trainerId))
        .sort((a,b) => a.sentAt.localeCompare(b.sentAt));
    },
    getAllMessagesForTrainer(trainerId) {
      // Returns last message per client conversation
      const msgs = get(K.messages, []).filter(m => m.fromId===trainerId || m.toId===trainerId);
      const byClient = {};
      msgs.forEach(m => {
        const cid = m.fromId===trainerId ? m.toId : m.fromId;
        if (!byClient[cid] || m.sentAt > byClient[cid].sentAt) byClient[cid] = m;
      });
      return byClient; // { clientId: lastMessage }
    },
    sendMessage(fromId, toId, text, fromRole) {
      const msgs = get(K.messages, []);
      const msg = { id:genId(), fromId, toId, from:fromRole, text, sentAt:new Date().toISOString(), read:false };
      msgs.push(msg);
      set(K.messages, msgs);
      _push('POST', '/api/messages', { fromId, toId, text, from: fromRole });
      // Store notification for trainer if client is sending
      if (fromRole === 'client') {
        const notifKey = 'sam_chat_notif_' + toId;
        const notifs = get(notifKey, []);
        const client = get(K.clients, []).find(c => c.id === fromId);
        const clientName = client ? (client.name + ' ' + (client.surname||'')).trim() : fromId;
        notifs.push({ id: msg.id, clientId: fromId, clientName, text, sentAt: msg.sentAt, shown: false });
        set(notifKey, notifs);
      }
      return msg;
    },
    // Get unshown chat notifications for trainer
    getChatNotifications(trainerId) {
      const key = 'sam_chat_notif_' + trainerId;
      return get(key, []).filter(n => !n.shown);
    },
    // Mark notifications as shown
    markChatNotificationsShown(trainerId) {
      const key = 'sam_chat_notif_' + trainerId;
      const notifs = get(key, []).map(n => ({...n, shown: true}));
      set(key, notifs);
    },
    markMessagesRead(trainerId, clientId) {
      const msgs = get(K.messages, []);
      msgs.forEach(m => {
        if (m.fromId===clientId && m.toId===trainerId) m.read = true;
      });
      set(K.messages, msgs);
      _push('POST', '/api/messages/read', { trainerId, clientId });
    },
    getUnreadCount(trainerId) {
      return get(K.messages, []).filter(m => m.toId===trainerId && !m.read).length;
    },

    // ── PROGRESS TRACKING ─────────────────────────────────────────────────────────
    // Entry: { id, clientId, date, weight, measurements:{chest,waist,hips,thigh},
    //          trainerNote, clientNote, photos:[] }
    getProgress(clientId) {
      return get(K.progress, []).filter(p => p.clientId===clientId)
        .sort((a,b) => b.date.localeCompare(a.date));
    },
    addProgress(clientIdOrEntry, maybeData) {
      const entry = maybeData ? { clientId: clientIdOrEntry, ...maybeData } : clientIdOrEntry;
      const prog = get(K.progress, []);
      const newEntry = { id:genId(), ...entry, createdAt:new Date().toISOString() };
      prog.push(newEntry);
      set(K.progress, prog);
      return newEntry;
    },
    updateProgress(id, updates) {
      const prog = get(K.progress, []);
      const idx = prog.findIndex(p => p.id === id);
      if (idx === -1) return null;
      Object.assign(prog[idx], updates);
      set(K.progress, prog);
      return prog[idx];
    },

    // ── PACKAGES ──────────────────────────────────────────────────────────────────
    // Package: { id, clientId, trainerId, total, used, price, name, createdAt, active }
    getPackages(clientId) {
      return get(K.packages, []).filter(p => p.clientId===clientId);
    },
    getActivePackage(clientId, trainerId) {
      return get(K.packages, []).find(p => p.clientId===clientId && p.trainerId===trainerId && p.active && p.used<p.total) || null;
    },
    createPackage(pkg) {
      const packages = get(K.packages, []);
      const newPkg = { id:genId(), ...pkg, used:0, active:true, createdAt:new Date().toISOString() };
      packages.push(newPkg);
      set(K.packages, packages);
      return newPkg;
    },
    usePackageSession(packageId) {
      const packages = get(K.packages, []);
      const idx = packages.findIndex(p => p.id===packageId);
      if (idx===-1) return null;
      packages[idx].used++;
      if (packages[idx].used >= packages[idx].total) packages[idx].active = false;
      set(K.packages, packages);
      return packages[idx];
    },

    // ── PAYMENTS ──────────────────────────────────────────────────────────────────
    // Payment: { id, clientId, trainerId, amount, method:'blik'|'bank'|'cash'|'package',
    //            description, status:'paid'|'pending', date, bookingId }
    getPayments(clientId) {
      return get(K.payments, []).filter(p => p.clientId===clientId)
        .sort((a,b) => b.date.localeCompare(a.date));
    },
    getClientBalance(clientId) {
      const clients = get(K.clients, []);
      const client = clients.find(c => c.id===clientId);
      // LOCAL date – brak błędu UTC
      const _n = new Date();
      const todayStr = _n.getFullYear()+'-'+String(_n.getMonth()+1).padStart(2,'0')+'-'+String(_n.getDate()).padStart(2,'0');

      // ── WPŁACONO ──────────────────────────────────────────────────────────────
      // 1. Potwierdzone płatności z tabeli sam_payments
      const payments = get(K.payments, []).filter(p =>
        p.clientId===clientId && (p.status==='paid'||p.status==='confirmed'));
      let paid = payments.reduce((s,p) => s+p.amount, 0);

      // 2. Legacy pakiety: zakupione przed wprowadzeniem rekordu płatności
      //    jeśli pakiet nie ma odpowiadającego rekordu w sam_payments – dolicz cenę
      const pkgs = get(K.packages, []).filter(p => p.clientId===clientId);
      pkgs.forEach(pkg => {
        const hasPay = payments.some(p => p.packageId === pkg.id);
        if (!hasPay) {
          paid += pkg.totalPrice || ((pkg.totalSessions||0) * (pkg.pricePerSession||200));
        }
      });

      // ── KOSZT SESJI ───────────────────────────────────────────────────────────
      const bookings = getB().filter(b =>
        client && (b.phone===client.phone || b.clientId===clientId) &&
        (b.status==='completed' || (b.status==='confirmed' && b.date <= todayStr))
      );
      const owed = bookings.reduce((s,b) => s+getBookingPrice(b), 0);

      // balance > 0 → kredyt (klient ma nadpłatę)
      // balance < 0 → dług  (sesje bez pokrycia w płatnościach)
      return { paid, owed, balance: paid - owed, sessionCount: bookings.length };
    },
    addPayment(payment) {
      const payments = get(K.payments, []);
      const newPay = { id:genId(), ...payment, bookingId: payment.bookingId || null, date:payment.date||new Date().toISOString().split('T')[0], status:payment.status||'pending' };
      payments.push(newPay);
      set(K.payments, payments);
      _push('POST', '/api/trainer/payments', newPay);
      return newPay;
    },
    confirmPayment(paymentId) {
      const payments = get(K.payments, []);
      const idx = payments.findIndex(p=>p.id===paymentId);
      if (idx===-1) return null;
      payments[idx].status = 'paid';
      payments[idx].confirmedAt = new Date().toISOString();
      set(K.payments, payments);
      _push('PATCH', `/api/trainer/payments/${paymentId}`, { status: 'paid' });
      return payments[idx];
    },
    rejectPayment(paymentId) {
      const payments = get(K.payments, []);
      const idx = payments.findIndex(p=>p.id===paymentId);
      if (idx===-1) return null;
      payments[idx].status = 'rejected';
      set(K.payments, payments);
      return payments[idx];
    },
    getPaymentsByBooking(bookingId) {
      return get(K.payments, []).filter(p => p.bookingId === bookingId);
    },
    recordCashPayment(bookingId, trainerId, clientId, amount) {
      const payments = get(K.payments, []);
      const pay = {
        id: genId(), clientId, trainerId, bookingId: bookingId || null,
        amount: amount || 0, method: 'cash',
        description: 'Gotówka – odebrana przez trenera',
        status: 'paid', date: new Date().toISOString(),
        confirmedAt: new Date().toISOString(), confirmedBy: 'trainer'
      };
      payments.push(pay);
      set(K.payments, payments);
      // Also update booking paymentStatus
      if (bookingId) {
        const bs = getB();
        const idx = bs.findIndex(b => b.id === bookingId);
        if (idx !== -1) { bs[idx].paymentStatus = 'paid'; saveB(bs); }
      }
      return pay;
    },
    getPaymentsForTrainer(trainerId) {
      // Get all clients of this trainer and their payments
      const clients = get(K.clients, []).filter(c=>c.trainerId===trainerId);
      const clientIds = clients.map(c=>c.id);
      return get(K.payments, [])
        .filter(p=>clientIds.includes(p.clientId))
        .sort((a,b)=>b.date.localeCompare(a.date));
    },
    getMethodBreakdown(trainerId) {
      const clients = get(K.clients, []).filter(c => c.trainerId === trainerId);
      const clientIds = clients.map(c => c.id);
      const payments = get(K.payments, [])
        .filter(p => clientIds.includes(p.clientId) && (p.status === 'paid' || p.status === 'confirmed'));
      const result = { cash: 0, blik: 0, bank: 0, card: 0, package: 0 };
      payments.forEach(p => {
        const m = p.method || 'cash';
        if (result[m] !== undefined) result[m] += (p.amount || 0);
        else result['cash'] += (p.amount || 0);
      });
      result.total = result.cash + result.blik + result.bank + result.card + result.package;
      return result;
    },
    // Delete a specific message
    deleteMessage(messageId) {
      const msgs = get(K.messages, []).filter(m => m.id !== messageId);
      set(K.messages, msgs);
    },
    // Clear all messages between trainer and client
    clearChat(trainerId, clientId) {
      const msgs = get(K.messages, []).filter(m =>
        !((m.fromId===trainerId&&m.toId===clientId)||(m.fromId===clientId&&m.toId===trainerId))
      );
      set(K.messages, msgs);
    },
    // Get packages for client
    getClientPackages(clientId) {
      return get(K.packages, []).filter(p => p.clientId === clientId).sort((a,b) => b.purchasedAt.localeCompare(a.purchasedAt));
    },
    // Buy a package
    buyPackage(clientId, trainerId, sessions, pricePerSession, method) {
      const pkgs = get(K.packages, []);
      const pkg = {
        id: genId(),
        clientId, trainerId,
        totalSessions: sessions,
        usedSessions: 0,
        pricePerSession: pricePerSession || 200,
        totalPrice: sessions * (pricePerSession || 200),
        method: method || 'bank',
        status: 'active',
        purchasedAt: new Date().toISOString(),
      };
      pkgs.push(pkg);
      set(K.packages, pkgs);
      // Also record a payment so balance is recalculated
      const payments = get(K.payments, []);
      payments.push({
        id: genId(),
        clientId, trainerId,
        amount: pkg.totalPrice,
        method: method || 'bank',
        description: `Pakiet ${sessions} sesji × ${pricePerSession||200} PLN`,
        status: 'paid',
        date: new Date().toISOString(),
        confirmedAt: new Date().toISOString(),
        packageId: pkg.id,
      });
      set(K.payments, payments);
      _push('POST', '/api/trainer/packages', { clientId, sessions, pricePerSession: pricePerSession||200, method: method||'bank' });
      return pkg;
    },
    // Use one session from package
    usePackage(packageId) {
      const pkgs = get(K.packages, []);
      const idx = pkgs.findIndex(p => p.id === packageId);
      if (idx === -1) return null;
      pkgs[idx].usedSessions = (pkgs[idx].usedSessions || 0) + 1;
      if (pkgs[idx].usedSessions >= pkgs[idx].totalSessions) pkgs[idx].status = 'used';
      set(K.packages, pkgs);
      return pkgs[idx];
    },
    // Get training plan files for client
    getTrainingPlan(clientId) {
      return get('sam_plans_' + clientId, []);
    },
    // Save training plan file (trainer uploads)
    saveTrainingPlan(clientId, trainerId, file) {
      // Warn if file data too large (20MB base64 limit)
      if (file.data && file.data.length > 27 * 1024 * 1024) {
        return { error: 'Plik jest zbyt duży (max 20MB)' };
      }
      const plans = get('sam_plans_' + clientId, []);
      const plan = {
        id: genId(), clientId, trainerId,
        name: file.name || file.title || 'Plan',
        link: file.link || null,
        notes: file.notes || '',
        data: file.data || null,       // base64 file content
        fileType: file.fileType || null,
        fileSize: file.fileSize || null,
        uploadedAt: new Date().toISOString()
      };
      plans.push(plan);
      set('sam_plans_' + clientId, plans);
      return plan;
    },
    // Delete training plan
    deleteTrainingPlan(clientId, planId) {
      const plans = get('sam_plans_' + clientId, []).filter(p => p.id !== planId);
      set('sam_plans_' + clientId, plans);
    },
    // Client cancel booking (with 24h check)
    cancelClientBooking(bookingId, clientId) {
      const bs = getB();
      const idx = bs.findIndex(b => b.id === bookingId);
      if (idx === -1) return { error: 'Nie znaleziono treningu.' };
      const b = bs[idx];
      // Check 24h rule
      const now = new Date();
      const trainingDT = new Date(b.date + 'T' + (b.time || '00:00') + ':00');
      const hoursLeft = (trainingDT - now) / 3600000;
      if (hoursLeft < 24) return { error: 'Nie można anulować mniej niż 24h przed treningiem.' };
      bs[idx].status = 'cancelled';
      bs[idx].cancelledBy = 'client';
      bs[idx].cancelledAt = new Date().toISOString();
      saveB(bs);
      _push('PATCH', `/api/client/bookings/${bookingId}/cancel`, {});
      return { success: true };
    },

    // Auto-use one package session when booking is confirmed (if client has active package)
    // Guard: stores bookingId in pkg.usedForBookings to prevent double-decrement
    autoUsePackageForBooking(bookingId) {
      const b = getB().find(x => x.id === bookingId);
      if (!b) return null;
      // Find clientId: from booking.clientId or match by phone in sam_clients
      let clientId = b.clientId || null;
      if (!clientId && b.phone) {
        const cl = get(K.clients, []).find(c => c.phone === b.phone);
        if (cl) clientId = cl.id;
      }
      if (!clientId) return null;
      const pkgs = get(K.packages, []);
      // Double-decrement guard: check no package was already used for this booking
      const alreadyUsed = pkgs.some(p => (p.usedForBookings || []).includes(bookingId));
      if (alreadyUsed) return null;
      const pkg = pkgs.find(p =>
        p.clientId === clientId &&
        (!p.trainerId || p.trainerId === b.trainerId) &&
        p.status === 'active' &&
        (p.usedSessions || 0) < p.totalSessions
      );
      if (!pkg) return null;
      // Record bookingId in package before decrementing
      const idx = pkgs.findIndex(p => p.id === pkg.id);
      pkgs[idx].usedForBookings = [...(pkgs[idx].usedForBookings || []), bookingId];
      set(K.packages, pkgs);
      return this.usePackage(pkg.id);
    },

    // ── TANITA MEASUREMENTS ───────────────────────────────────────────────────────
    getMeasurements(clientId) {
      return get('sam_measurements_' + clientId, []).sort((a,b) => b.takenAt.localeCompare(a.takenAt));
    },
    saveMeasurement(clientId, trainerId, measureData) {
      if (measureData.data && measureData.data.length > 27 * 1024 * 1024) {
        return { error: 'Plik jest zbyt duży (max 20MB)' };
      }
      const ms = get('sam_measurements_' + clientId, []);
      const m = {
        id: genId(),
        clientId, trainerId,
        title: measureData.title || 'Pomiar Tanita',
        notes: measureData.notes || '',
        data: measureData.data || null,
        fileType: measureData.fileType || 'application/pdf',
        fileSize: measureData.fileSize || null,
        takenAt: measureData.takenAt || new Date().toISOString(),
        uploadedAt: new Date().toISOString(),
      };
      ms.push(m);
      set('sam_measurements_' + clientId, ms);
      return m;
    },
    deleteMeasurement(clientId, measureId) {
      const ms = get('sam_measurements_' + clientId, []).filter(m => m.id !== measureId);
      set('sam_measurements_' + clientId, ms);
      return { success: true };
    },
    // Client adds/edits their comment on a Tanita measurement
    addMeasurementComment(clientId, measId, comment) {
      const ms = get('sam_measurements_' + clientId, []);
      const idx = ms.findIndex(m => m.id === measId);
      if (idx === -1) return { error: 'Nie znaleziono pomiaru.' };
      ms[idx].clientComment = comment.trim();
      ms[idx].commentedAt = new Date().toISOString();
      set('sam_measurements_' + clientId, ms);
      return { success: true };
    },

    // Alias for compatibility
    patchClient(id, updates) { return this.updateClient(id, updates); },

    // ══════════════════════════════════════════════════════════════════════════
    // ── SERVER SYNC (async – used on page load & login) ──────────────────────
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Authenticate with the server and obtain a session cookie.
     * Falls back to the local password check if the server is unreachable.
     * Returns the trainer/client object or null on failure.
     */
    async _loginTrainer(id, pw) {
      if (_IS_PROD) {
        const data = await _apiJSON('POST', '/api/auth/trainer/login', { id, password: pw });
        if (data?.success) return data.trainer;
        // Server unreachable or bad creds – fall back to local
      }
      return this.verifyTrainer(id, pw);
    },
    async _loginClient(email, pw) {
      if (_IS_PROD) {
        const data = await _apiJSON('POST', '/api/auth/client/login', { email, password: pw });
        if (data?.success) return data.client;
      }
      return this.verifyClient(email, pw);
    },
    async _logoutServer() {
      if (_IS_PROD) await _apiFetch('POST', '/api/auth/logout', {}).catch(() => {});
    },

    /**
     * Pull all data from the server and store in localStorage.
     * Call once after login (trainer or client).
     * role = 'trainer' | 'client'
     */
    async _syncDown(role) {
      if (!_IS_PROD) return;
      try {
        const data = await _apiJSON('GET', `/api/${role}/sync`);
        if (!data) return;
        // Trainer data
        if (data.bookings)     set(K.bookings, data.bookings);
        if (data.packages)     set(K.packages, data.packages);
        if (data.payments)     set(K.payments, data.payments);
        if (data.messages)     set(K.messages, data.messages);
        // Trainer-only
        if (data.clients)      set(K.clients, data.clients);
        if (data.settings)     set(K.settings, data.settings);
        if (data.availability && data.trainerId) {
          const a = getA();
          a[data.trainerId] = data.availability;
          saveA(a);
        }
        if (data.blocked && data.trainerId) {
          const bl = get(K.blocked, {});
          bl[data.trainerId] = data.blocked;
          set(K.blocked, bl);
        }
        if (data.prices && data.trainerId) {
          const cp = get(K.clientPrices, {});
          cp[data.trainerId] = data.prices;
          set(K.clientPrices, cp);
        }
        if (data.notes && data.trainerId) {
          const nt = get(K.notes, {});
          nt[data.trainerId] = data.notes;
          set(K.notes, nt);
        }
        // Client: update self
        if (data.client) {
          const cls = get(K.clients, []);
          const idx = cls.findIndex(c => c.id === data.client.id);
          if (idx !== -1) cls[idx] = {...cls[idx], ...data.client};
          else cls.push(data.client);
          set(K.clients, cls);
        }
        // Measurements – stored per clientId
        if (data.measurements) {
          const byClient = {};
          data.measurements.forEach(m => {
            if (!byClient[m.clientId]) byClient[m.clientId] = [];
            byClient[m.clientId].push(m);
          });
          Object.entries(byClient).forEach(([cid, ms]) => {
            set('sam_measurements_' + cid, ms);
          });
        }
        console.log('[SAM] Sync down complete from server.');
      } catch(e) {
        console.warn('[SAM] Sync down failed, using local data:', e.message);
      }
    },

    /** Push local data to server (used after writes when _push wasn't available) */
    async _refreshServerSession() {
      if (!_IS_PROD) return;
      const data = await _apiJSON('GET', '/api/auth/me');
      return data; // { type, id, name, ... } or null
    },

    // Trainer explicitly marks training as done
    markTrainingCompleted(bookingId, trainerId) {
      const bs = getB();
      const idx = bs.findIndex(b => b.id === bookingId);
      if (idx === -1) return { error: 'Nie znaleziono treningu.' };
      if (bs[idx].trainerId !== trainerId && trainerId) return { error: 'Brak uprawnień.' };
      bs[idx].status = 'completed';
      bs[idx].completedAt = new Date().toISOString();
      saveB(bs);
      _push('PATCH', `/api/trainer/booking/${bookingId}`, { status: 'completed' });
      return { success: true };
    },

    // Admin adds a manual booking record (e.g. completed training off-system)
    addManualTrainingRecord(payload) {
      const bs = getB();
      const b = {
        id: genId(),
        trainer: payload.trainer||'', trainerId: payload.trainerId||'',
        date: payload.date, time: payload.time||'00:00',
        name: payload.name||'', surname: payload.surname||'',
        phone: payload.phone||'', email: payload.email||'',
        trainingType: payload.trainingType||'inperson',
        clientMessage: '', language:'pl', recurring:false, recurringWeeks:1,
        paymentChoice: payload.paymentChoice||'cash',
        paymentStatus: payload.paymentStatus||'paid',
        meetLink: null, createdAt: new Date().toISOString(),
        status: 'confirmed', smsHistory:[], adminNotes: payload.note||'Dodane ręcznie przez administratora',
        bookedBy:'admin', _local:true,
      };
      bs.push(b); saveB(bs);
      return { success:true, booking:b };
    },
  };
})();
