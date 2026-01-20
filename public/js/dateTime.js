// ---- Digital Clock ----
function updateLocalTime() {
    const now = new Date();
    document.getElementById("localTime").innerText = now.toLocaleTimeString();
    document.getElementById("todayDate").innerText = now.toLocaleDateString();
}

const serverTimeEl = document.getElementById("serverTime");
let serverTime = new Date(serverTimeEl.dataset.time);

function updateServerTime() {
    serverTimeEl.innerText = serverTime.toLocaleTimeString();
    serverTime.setSeconds(serverTime.getSeconds() + 1);
}

// ---- Calendar Logic ----
const calendarBody = document.getElementById("calendarBody");
const monthYear = document.getElementById("monthYear");
let currentDate = new Date();

function renderCalendar(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const today = new Date();

    const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    monthYear.innerText = `${monthNames[month]} ${year}`;

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    calendarBody.innerHTML = "";

    // Empty slots for first day alignment
    for(let i=0; i<firstDay; i++){
        const empty = document.createElement("div");
        calendarBody.appendChild(empty);
    }

    // Fill days
    for(let d=1; d<=daysInMonth; d++){
        const dayEl = document.createElement("div");
        dayEl.classList.add("calendar-day");
        if(d === today.getDate() && month === today.getMonth() && year === today.getFullYear()){
            dayEl.classList.add("today");
        }
        dayEl.innerText = d;
        calendarBody.appendChild(dayEl);
    }
}

// Navigation buttons
document.getElementById("prevMonth").addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar(currentDate);
});
document.getElementById("nextMonth").addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar(currentDate);
});

// ---- Run everything ----
setInterval(() => {
    updateLocalTime();
    updateServerTime();
}, 1000);

updateLocalTime();
updateServerTime();
renderCalendar(currentDate);
