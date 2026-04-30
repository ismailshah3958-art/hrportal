<template>
    <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
        <div
            v-if="state.birthdaysError"
            class="shrink-0 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-xs text-amber-100"
        >
            {{ state.birthdaysError }}
        </div>

        <div
            v-if="state.loading && isHr"
            class="min-h-0 flex-1 rounded-2xl border border-white/10 bg-white/[0.03] px-6 py-8 text-center text-sm text-slate-500"
        >
            Loading company timeline…
        </div>
        <div
            v-if="state.loading && !isHr"
            class="flex min-h-0 flex-1 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.03] p-12 text-sm text-slate-500"
        >
            Loading your dashboard…
        </div>

        <!-- HR: 3 columns — 25% / 50% / 25% (grid-cols-12: 3+6+3) -->
        <template v-if="!state.loading && isHr">
            <p class="mb-4 shrink-0 text-sm text-slate-400">
                <span v-if="me.employee">Hi, {{ me.employee.full_name }}</span>
                <span v-else-if="me.user?.name">Hi, {{ me.user.name }}</span>
                <span v-else>HR overview</span>
            </p>

            <div class="grid min-h-0 flex-1 grid-cols-1 gap-6 overflow-y-auto overscroll-contain lg:grid-cols-12 lg:overflow-hidden lg:pr-1">
                <!-- Left 25%: intro, snapshot, modules -->
                <div class="space-y-4 lg:col-span-3 lg:min-h-0 lg:min-w-0 lg:overflow-y-auto lg:pr-1">
                    <div
                        class="relative overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-emerald-950/40 via-[#121820] to-[#0c1016] p-4 ring-1 ring-white/5"
                    >
                        <div
                            class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-emerald-500/20 blur-2xl"
                        />
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-300/90">HR overview</p>
                        <p v-if="me.employee" class="mt-2 text-xs text-slate-500">
                            <span class="font-mono text-emerald-200/80">{{ me.employee.employee_code }}</span>
                            <span v-if="me.employee.designation_name"> · {{ me.employee.designation_name }}</span>
                        </p>
                        <p v-else class="mt-2 text-xs text-white/90">Company news and birthdays are in the center and right.</p>
                    </div>

                    <div
                        v-if="state.hiddenMetrics.length"
                        class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-[10px] text-amber-100"
                    >
                        Some metrics hidden: {{ state.hiddenMetrics.join(', ') }}.
                    </div>

                    <section>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Snapshot</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <article
                                v-for="card in stats"
                                :key="card.title"
                                class="group rounded-xl border border-white/10 bg-white/[0.03] p-3 transition hover:border-emerald-500/30"
                            >
                                <p class="text-[10px] font-medium text-slate-500">{{ card.title }}</p>
                                <p class="mt-1 text-xl font-semibold tabular-nums text-white">{{ card.value }}</p>
                                <span
                                    class="mt-1 flex h-7 w-7 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-400 [&>svg]:block"
                                    v-html="card.icon"
                                ></span>
                            </article>
                        </div>
                    </section>

                    <section>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Quick links</h3>
                        <div class="flex flex-col gap-2">
                            <button
                                type="button"
                                class="w-full rounded-lg border border-dashed border-emerald-500/30 bg-emerald-500/5 px-3 py-2.5 text-left text-xs text-slate-300 transition hover:bg-emerald-500/10"
                                @click="router.push('/employees')"
                            >
                                <span class="font-medium text-emerald-200">Employees</span>
                                <span class="ml-1 text-slate-500">→</span>
                            </button>
                            <button
                                type="button"
                                class="w-full rounded-lg border border-dashed border-white/15 bg-white/[0.02] px-3 py-2.5 text-left text-xs text-slate-300 transition hover:bg-white/[0.04]"
                                @click="router.push('/attendance')"
                            >
                                <span class="font-medium text-slate-200">Attendance</span>
                                <span class="ml-1 text-slate-500">→</span>
                            </button>
                        </div>
                    </section>
                </div>

                <!-- Center 50%: announcements -->
                <div class="space-y-3 lg:col-span-6 lg:min-h-0 lg:min-w-0 lg:overflow-y-auto lg:pr-1">
                    <div class="flex items-end justify-between gap-2 border-b border-white/10 pb-2">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Announcements</h3>
                        <RouterLink
                            v-if="me.flags?.hr_announcements_manage"
                            to="/announcements"
                            class="text-xs font-medium text-emerald-400 hover:text-emerald-300"
                        >Manage</RouterLink>
                    </div>
                    <p
                        v-if="!feed.length"
                        class="rounded-2xl border border-dashed border-white/15 bg-white/[0.02] px-4 py-10 text-center text-sm text-slate-500"
                    >
                        No announcements yet.
                    </p>
                    <ul v-else class="space-y-3">
                        <li
                            v-for="a in feed"
                            :key="a.id"
                            class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5"
                        >
                            <div
                                class="h-1 w-full bg-gradient-to-r from-emerald-500/60 to-emerald-800/30"
                                :class="a.is_pinned ? 'from-amber-500/80' : ''"
                            />
                            <button
                                type="button"
                                class="block w-full p-3 text-left transition hover:bg-white/[0.02] sm:p-4"
                                @click="openAnnouncement(a)"
                            >
                                <p class="text-[11px] text-slate-500">{{ formatPublished(a.published_at) }}</p>
                                <h4 class="mt-1 text-sm font-semibold text-white">
                                    <span v-if="a.is_pinned" class="text-amber-400">* </span>{{ a.title }}
                                </h4>
                                <p class="mt-2 truncate text-xs leading-relaxed text-slate-400">{{ oneLineAnnouncement(a) }}</p>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Right 25%: time, calendar, birthdays -->
                <div class="space-y-4 lg:col-span-3 lg:min-h-0 lg:min-w-0 lg:overflow-y-auto lg:pr-1">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 ring-1 ring-white/5">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Local time</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums text-white">{{ essTime || '—' }}</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-3 ring-1 ring-white/5">
                        <h3 class="text-sm font-semibold text-white">{{ calendarGrid.monthLabel }}</h3>
                        <div class="mt-2 grid grid-cols-7 gap-0.5 text-center text-[8px] font-semibold text-slate-500">
                            <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                        </div>
                        <div class="mt-0.5 space-y-0.5">
                            <div v-for="(week, wi) in calendarGrid.weeks" :key="'hrw-' + wi" class="grid grid-cols-7 gap-0.5">
                                <div
                                    v-for="(cell, ci) in week"
                                    :key="ci"
                                    class="flex aspect-square min-h-0 items-center justify-center rounded text-[9px]"
                                    :class="cell.day ? (cell.isToday ? 'bg-emerald-600 font-bold text-white' : 'bg-white/5 text-slate-300') : ''"
                                >
                                    {{ cell.day || '' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <section v-if="todayBirthdays.length">
                        <h3 class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-amber-400/90">Today</h3>
                        <ul class="space-y-2">
                            <li v-for="b in todayBirthdays" :key="'t-' + b.employee_id">
                                <BirthdayCard :row="b" />
                            </li>
                        </ul>
                    </section>
                    <section v-if="soonBirthdays.length">
                        <h3 class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Next 2 wks</h3>
                        <ul class="space-y-2">
                            <li v-for="b in soonBirthdays" :key="'s-' + b.employee_id">
                                <BirthdayCard :row="b" />
                            </li>
                        </ul>
                    </section>
                    <section v-if="laterBirthdays.length">
                        <h3 class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Later (90d)</h3>
                        <ul class="space-y-1.5">
                            <li v-for="b in laterBirthdays" :key="'l-' + b.employee_id">
                                <BirthdayCard :row="b" compact />
                            </li>
                        </ul>
                    </section>
                    <p v-if="!birthdays.length && !state.birthdaysError" class="text-center text-xs text-slate-500">No birthday data.</p>
                </div>
            </div>
        </template>

        <!-- Employee: fixed shell — scroll inside columns on lg, single column scroll on mobile -->
        <template v-else-if="!state.loading && !isHr">
            <p class="mb-4 shrink-0 text-sm text-slate-400">Welcome back{{ me.employee ? `, ${me.employee.full_name}` : '' }}</p>

            <div class="grid min-h-0 flex-1 grid-cols-1 gap-6 overflow-y-auto overscroll-contain lg:grid-cols-12 lg:overflow-hidden lg:pr-1">
                <!-- Left 25%: profile, leave, shortcuts -->
                <div class="space-y-6 lg:col-span-3 lg:min-h-0 lg:min-w-0 lg:overflow-y-auto lg:pr-1">
                    <!-- Profile card -->
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 ring-1 ring-white/5">
                            <div class="flex items-start gap-4">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/10 bg-white/5 text-xl font-bold text-slate-300">
                                    <img
                                        v-if="me.employee?.profile_photo_url"
                                        :src="me.employee.profile_photo_url"
                                        alt=""
                                        class="h-full w-full object-cover"
                                    >
                                    <span v-else>{{ initials(me.employee?.full_name || 'U') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h2 class="truncate text-lg font-semibold text-white">{{ me.employee?.full_name || me.user?.name || 'Employee' }}</h2>
                                    <p class="mt-0.5 text-sm text-slate-400">
                                        {{ me.employee?.designation_name || '—' }}
                                        <span v-if="me.employee?.employee_code" class="font-mono text-emerald-200/80"> ({{ me.employee.employee_code }})</span>
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">{{ me.employee?.department_name || '—' }}</p>
                                </div>
                            </div>
                            <div class="mt-5 flex items-center gap-3">
                                <div class="relative h-14 w-14 shrink-0">
                                    <div
                                        class="absolute inset-0 rounded-full p-0.5"
                                        :style="{
                                            background: `conic-gradient(rgb(16 185 129) ${profileProgress * 3.6}deg, rgba(255,255,255,0.12) 0deg)`,
                                        }"
                                    />
                                    <div class="absolute inset-0.5 flex items-center justify-center rounded-full bg-[#0c1016] text-[10px] font-bold text-emerald-300">
                                        {{ profileProgress }}%
                                    </div>
                                </div>
                                <p class="text-xs leading-relaxed text-slate-500">Profile completeness (photo, name, dept, DOB on file).</p>
                            </div>
                    </div>

                    <!-- Leave / requests table -->
                    <div
                        class="overflow-x-auto overflow-y-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5"
                        v-if="me.flags?.ess_leave_apply && me.employee"
                    >
                        <div class="border-b border-white/10 bg-white/[0.04] px-3 py-2.5">
                            <h3 class="text-xs font-semibold text-white">My leave requests</h3>
                        </div>
                        <div v-if="leaveLoadError" class="px-3 py-2 text-xs text-amber-200/90">{{ leaveLoadError }}</div>
                        <table v-else class="min-w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-white/10 text-[10px] text-slate-500">
                                    <th class="px-2 py-1.5 font-medium">Type</th>
                                    <th class="px-2 py-1.5 font-medium">Dates</th>
                                    <th class="px-2 py-1.5 font-medium">Days</th>
                                    <th class="px-2 py-1.5 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!leaveRows.length">
                                    <td colspan="4" class="px-3 py-4 text-center text-slate-500">No leave requests yet.</td>
                                </tr>
                                <tr v-for="row in leaveRows" :key="row.id" class="border-b border-white/5 last:border-0">
                                    <td class="max-w-[5rem] truncate px-2 py-1.5 text-slate-200">{{ row.leave_type?.name || '—' }}</td>
                                    <td class="whitespace-nowrap px-2 py-1.5 text-[10px] text-slate-400">{{ formatLeaveRange(row) }}</td>
                                    <td class="px-2 py-1.5 tabular-nums text-slate-300">{{ row.total_days }}</td>
                                    <td class="px-2 py-1.5">
                                        <span
                                            class="inline-flex rounded-full px-1.5 py-0.5 text-[9px] font-semibold uppercase"
                                            :class="leaveStatusClass(row.status)"
                                        >{{ row.status }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="border-t border-white/10 bg-white/[0.03] px-3 py-2 text-right">
                            <router-link to="/my/leave" class="text-[10px] font-semibold text-emerald-400 hover:text-emerald-300">Go to leave →</router-link>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <router-link
                            v-if="me.flags?.ess_attendance_view && me.employee"
                            to="/my/attendance"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-white/10 bg-white/5 px-4 py-2.5 text-center text-xs font-medium text-slate-200 transition hover:bg-white/10"
                        >Attendance</router-link>
                        <router-link
                            v-if="me.flags?.ess_leave_apply && me.employee"
                            to="/my/leave"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-white/10 bg-white/5 px-4 py-2.5 text-center text-xs font-medium text-slate-200 transition hover:bg-white/10"
                        >Leave</router-link>
                        <router-link
                            v-if="me.flags?.ess_payslip_view && me.employee"
                            to="/my/payslips"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-white/10 bg-white/5 px-4 py-2.5 text-center text-xs font-medium text-slate-200 transition hover:bg-white/10"
                        >Payslips</router-link>
                    </div>
                </div>

                <!-- Center 50%: announcements -->
                <div class="space-y-4 lg:col-span-6 lg:min-h-0 lg:min-w-0 lg:overflow-y-auto lg:pr-1">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Announcements</h3>
                    <p
                        v-if="!feed.length"
                        class="rounded-2xl border border-dashed border-white/15 bg-white/[0.02] px-4 py-12 text-center text-sm text-slate-500"
                    >
                        No announcements yet.
                    </p>
                    <ul v-else class="space-y-4">
                        <li
                            v-for="a in feed"
                            :key="a.id"
                            class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5"
                        >
                            <div
                                class="h-1 w-full bg-gradient-to-r from-emerald-500/60 to-emerald-800/30"
                                :class="a.is_pinned ? 'from-amber-500/80' : ''"
                            />
                            <button
                                type="button"
                                class="flex w-full gap-4 p-4 text-left transition hover:bg-white/[0.02] sm:p-5"
                                @click="openAnnouncement(a)"
                            >
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-xs font-bold text-slate-400">HR</div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-white">HR Portal</p>
                                    <p class="text-[11px] text-slate-500">{{ formatPublished(a.published_at) }}</p>
                                    <h4 class="mt-2 text-base font-semibold text-slate-100">{{ a.title }}</h4>
                                    <p class="mt-2 truncate text-sm leading-relaxed text-slate-400">{{ oneLineAnnouncement(a) }}</p>
                                </div>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Right 25% -->
                <div class="space-y-5 lg:col-span-3 lg:min-h-0 lg:min-w-0 lg:overflow-y-auto lg:pr-1">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 ring-1 ring-white/5">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Local time</p>
                        <p class="mt-1 text-3xl font-bold tabular-nums tracking-tight text-white">{{ essTime }}</p>
                        <p v-if="myBirthdayLine" class="mt-3 border-t border-white/10 pt-3 text-sm text-amber-200/90">{{ myBirthdayLine }}</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 ring-1 ring-white/5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Upcoming birthdays</h3>
                        <p v-if="!upcomingBirthdaysSidebar.length" class="mt-2 text-sm text-slate-500">No upcoming birthdays in the next 90 days.</p>
                        <ul v-else class="mt-3 space-y-2">
                            <li v-for="b in upcomingBirthdaysSidebar" :key="'sb-' + b.employee_id" class="flex items-center gap-2 text-sm text-slate-200">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full border border-white/10 bg-white/5 text-[10px] font-bold">
                                    <img v-if="b.profile_photo_url" :src="b.profile_photo_url" alt="" class="h-full w-full object-cover">
                                    <span v-else class="text-slate-400">{{ initials(b.full_name) }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-slate-200">{{ b.full_name }} <span v-if="b.is_today" class="text-amber-300">· Today</span></p>
                                    <p class="text-[10px] text-slate-500">{{ formatShortDate(b.next_birthday_on) }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Calendar -->
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 ring-1 ring-white/5">
                        <h3 class="text-sm font-semibold text-white">{{ calendarGrid.monthLabel }}</h3>
                        <div class="mt-2 grid grid-cols-7 gap-0.5 text-center text-[9px] font-semibold text-slate-500">
                            <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                        </div>
                        <div class="mt-1 space-y-0.5">
                            <div v-for="(week, wi) in calendarGrid.weeks" :key="wi" class="grid grid-cols-7 gap-0.5">
                                <div
                                    v-for="(cell, ci) in week"
                                    :key="ci"
                                    class="flex aspect-square min-h-0 items-center justify-center rounded-md text-[10px]"
                                    :class="cell.day ? (cell.isToday ? 'bg-emerald-600 font-bold text-white ring-1 ring-emerald-400/50' : 'bg-white/5 text-slate-300') : 'bg-transparent'"
                                >
                                    {{ cell.day || '' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Latest pay mix (moved from left) -->
                    <div
                        v-if="me.flags?.ess_payslip_view"
                        class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 ring-1 ring-white/5"
                    >
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Latest pay mix</h3>
                        <p v-if="payslipLoading" class="mt-2 text-sm text-slate-500">Loading…</p>
                        <template v-else-if="latestPayslip">
                            <p class="mt-1 text-[10px] text-slate-500">{{ payPeriodLabel(latestPayslip) }}</p>
                            <div class="mt-3 flex h-28 items-end justify-center gap-2">
                                <div class="flex w-6 flex-col items-center gap-0.5">
                                    <div class="w-full rounded-t-md bg-white/20" :style="{ height: payBarHeights.g + '%' }"></div>
                                    <span class="text-[8px] text-slate-500">Gross</span>
                                </div>
                                <div class="flex w-6 flex-col items-center gap-0.5">
                                    <div class="w-full rounded-t-md bg-emerald-500/80" :style="{ height: payBarHeights.a + '%' }"></div>
                                    <span class="text-[8px] text-slate-500">+Add</span>
                                </div>
                                <div class="flex w-6 flex-col items-center gap-0.5">
                                    <div class="w-full rounded-t-md bg-amber-500/70" :style="{ height: payBarHeights.d + '%' }"></div>
                                    <span class="text-[8px] text-slate-500">−Ded</span>
                                </div>
                            </div>
                            <p class="mt-2 text-center text-sm font-semibold text-white">Net {{ formatMoney(latestPayslip.net_amount) }}</p>
                        </template>
                        <p v-else class="mt-2 text-xs text-slate-500">No payslip data yet.</p>
                    </div>

                    <!-- Salary — additions & deductions (moved from left) -->
                    <div
                        v-if="me.flags?.ess_payslip_view"
                        class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 ring-1 ring-white/5"
                    >
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Salary — additions &amp; deductions</h3>
                        <p v-if="payslipLoading" class="mt-2 text-xs text-slate-500">Loading…</p>
                        <template v-else-if="latestPayslip">
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <div class="rounded-lg border border-white/10 bg-white/[0.04] p-2 text-center">
                                    <p class="text-[9px] font-medium uppercase text-slate-500">Gross</p>
                                    <p class="mt-0.5 text-xs font-semibold text-white tabular-nums">{{ formatMoney(latestPayslip.gross_amount) }}</p>
                                </div>
                                <div class="rounded-lg border border-emerald-500/25 bg-emerald-500/10 p-2 text-center">
                                    <p class="text-[9px] font-medium uppercase text-emerald-300/90">Additions</p>
                                    <p class="mt-0.5 text-xs font-semibold text-emerald-200 tabular-nums">+{{ formatMoney(latestPayslip.total_allowances) }}</p>
                                </div>
                                <div class="rounded-lg border border-amber-500/25 bg-amber-500/10 p-2 text-center">
                                    <p class="text-[9px] font-medium uppercase text-amber-200/90">Deductions</p>
                                    <p class="mt-0.5 text-xs font-semibold text-amber-200 tabular-nums">−{{ formatMoney(latestPayslip.total_deductions) }}</p>
                                </div>
                                <div class="rounded-lg border border-sky-500/30 bg-sky-500/10 p-2 text-center">
                                    <p class="text-[9px] font-medium uppercase text-sky-200/90">Net</p>
                                    <p class="mt-0.5 text-xs font-semibold text-sky-100 tabular-nums">{{ formatMoney(latestPayslip.net_amount) }}</p>
                                </div>
                            </div>
                            <router-link
                                to="/my/payslips"
                                class="mt-3 inline-flex text-xs font-medium text-emerald-400 transition hover:text-emerald-300"
                            >View all payslips →</router-link>
                        </template>
                        <p v-else class="mt-2 text-xs text-slate-500">No payroll run for you yet.</p>
                    </div>
                </div>
            </div>
        </template>

        <div
            v-if="announcementModal.open"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-black/70 p-4"
            @click.self="closeAnnouncement"
        >
            <div class="w-full max-w-3xl rounded-2xl border border-white/10 bg-[#0f1419] shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-white/10 px-4 py-3 sm:px-5">
                    <div class="min-w-0">
                        <p class="text-[11px] text-slate-500">{{ formatPublished(announcementModal.item?.published_at) }}</p>
                        <h3 class="mt-1 truncate text-base font-semibold text-white">
                            {{ announcementModal.item?.title || 'Announcement' }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        class="rounded-md border border-white/10 px-2 py-1 text-xs text-slate-300 hover:bg-white/5"
                        @click="closeAnnouncement"
                    >
                        Close
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto px-4 py-4 sm:px-5">
                    <p class="whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-300">
                        {{ announcementModal.item?.body || announcementModal.item?.excerpt || 'No details available.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, onMounted, onUnmounted, h, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const latestPayslip = ref(null);
const payslipLoading = ref(false);

const me = reactive({
    user: null,
    employee: null,
    flags: {},
});

const feed = reactive([]);
const birthdays = reactive([]);

const state = reactive({
    loading: true,
    birthdaysError: '',
    hiddenMetrics: [],
    metrics: {
        employees: 0,
        onLeaveToday: 0,
        pendingApprovals: 0,
        openPositions: 0,
    },
});

const isHr = computed(() => !!me.flags?.hr_dashboard);

const todayBirthdays = computed(() => birthdays.filter((b) => b.is_today));
const soonBirthdays = computed(() =>
    birthdays.filter((b) => !b.is_today && b.days_until >= 1 && b.days_until <= 14)
);
const laterBirthdays = computed(() => birthdays.filter((b) => !b.is_today && b.days_until > 14));

const leaveRows = ref([]);
const leaveLoadError = ref('');
const essTime = ref('');
let essTimer = null;

const calendarGrid = computed(() => {
    const now = new Date();
    const y = now.getFullYear();
    const m = now.getMonth();
    const first = new Date(y, m, 1);
    const startPad = (first.getDay() + 6) % 7;
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const todayStr = new Date().toDateString();
    const cells = [];
    for (let i = 0; i < startPad; i++) {
        cells.push({ day: null, isToday: false });
    }
    for (let d = 1; d <= daysInMonth; d++) {
        const dt = new Date(y, m, d);
        cells.push({ day: d, isToday: dt.toDateString() === todayStr });
    }
    while (cells.length % 7 !== 0) {
        cells.push({ day: null, isToday: false });
    }
    const weeks = [];
    for (let i = 0; i < cells.length; i += 7) {
        weeks.push(cells.slice(i, i + 7));
    }
    return {
        monthLabel: now.toLocaleString(undefined, { month: 'long', year: 'numeric' }),
        weeks,
    };
});

const profileProgress = computed(() => {
    const e = me.employee;
    if (!e) {
        return 0;
    }
    let n = 0;
    if (e.profile_photo_url) {
        n += 1;
    }
    if (e.full_name) {
        n += 1;
    }
    if (e.department_name || e.designation_name) {
        n += 1;
    }
    if (e.date_of_birth) {
        n += 1;
    }
    return Math.round((n / 4) * 100);
});

const payBarHeights = computed(() => {
    const p = latestPayslip.value;
    if (!p) {
        return { g: 0, a: 0, d: 0 };
    }
    const g = Number(p.gross_amount) || 0;
    const add = Number(p.total_allowances) || 0;
    const ded = Number(p.total_deductions) || 0;
    const max = Math.max(g, add, ded, 1);
    return {
        g: Math.round((g / max) * 100),
        a: Math.round((add / max) * 100),
        d: Math.round((ded / max) * 100),
    };
});

const upcomingBirthdaysSidebar = computed(() => birthdays.slice(0, 8));
const announcementModal = reactive({
    open: false,
    item: null,
});

function formatLeaveRange(row) {
    if (!row?.start_date || !row?.end_date) {
        return '—';
    }
    return `${formatShortDate(row.start_date)} – ${formatShortDate(row.end_date)}`;
}

function leaveStatusClass(status) {
    const s = String(status || '').toLowerCase();
    if (s === 'approved') {
        return 'bg-emerald-500/15 text-emerald-300';
    }
    if (s === 'rejected' || s === 'denied') {
        return 'bg-red-500/15 text-red-300';
    }
    if (s === 'pending') {
        return 'bg-amber-500/15 text-amber-200';
    }
    return 'bg-white/10 text-slate-300';
}

async function fetchMyLeave() {
    if (!me.employee?.id) {
        return;
    }
    leaveLoadError.value = '';
    try {
        const { data } = await window.axios.get(`/api/employees/${me.employee.id}/leave-requests`);
        leaveRows.value = data?.data ?? [];
    } catch (e) {
        if (e?.response?.status === 403) {
            leaveLoadError.value = 'Leave list is not available for your account.';
        } else {
            leaveLoadError.value = e?.response?.data?.message ?? 'Could not load leave requests.';
        }
        leaveRows.value = [];
    }
}

function initials(name) {
    if (!name) return '?';
    const parts = String(name).trim().split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return String(name).slice(0, 2).toUpperCase();
}

function formatShortDate(isoDate) {
    if (!isoDate) return '';
    try {
        return new Date(isoDate + 'T12:00:00').toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return isoDate;
    }
}

function formatPublished(iso) {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
    } catch {
        return '';
    }
}

function oneLineAnnouncement(row) {
    const text = String(row?.body || row?.excerpt || '').replace(/\s+/g, ' ').trim();
    return text || 'No details.';
}

function openAnnouncement(row) {
    announcementModal.item = row ?? null;
    announcementModal.open = !!row;
}

function closeAnnouncement() {
    announcementModal.open = false;
    announcementModal.item = null;
}

function formatMoney(v) {
    const n = Number(v ?? 0);
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        maximumFractionDigits: 0,
    }).format(Number.isNaN(n) ? 0 : n);
}

function payPeriodLabel(row) {
    const y = row?.payroll_run?.period_year;
    const m = row?.payroll_run?.period_month;
    if (!y || !m) {
        return '—';
    }
    try {
        return new Date(Number(y), Number(m) - 1, 1).toLocaleString(undefined, {
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return `${y}-${m}`;
    }
}

function daysUntilNextBirthdayFromDob(dobStr) {
    if (!dobStr) return null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const d = new Date(dobStr + 'T12:00:00');
    const month = d.getMonth();
    const day = d.getDate();
    let next = new Date(today.getFullYear(), month, day);
    next.setHours(0, 0, 0, 0);
    if (next < today) {
        next = new Date(today.getFullYear() + 1, month, day);
        next.setHours(0, 0, 0, 0);
    }
    return Math.round((next - today) / 86400000);
}

const myBirthdayLine = computed(() => {
    const dob = me.employee?.date_of_birth;
    if (!dob) return '';
    const days = daysUntilNextBirthdayFromDob(dob);
    if (days === null) return '';
    if (days === 0) return 'It’s your birthday today — happy birthday from the team.';
    if (days === 1) return 'Your birthday is tomorrow.';
    return `Your next birthday is in ${days} days.`;
});

async function safeGet(url, label) {
    try {
        const { data } = await window.axios.get(url);
        return { ok: true, data };
    } catch (e) {
        if (e?.response?.status === 403) {
            state.hiddenMetrics.push(label);
            return { ok: false, data: null };
        }
        throw e;
    }
}

async function fetchEmployeeCount() {
    const first = await safeGet('/api/employees?per_page=5&page=1', 'employees');
    if (!first.ok) return 0;
    return Number(first.data?.meta?.total ?? (first.data?.data ?? []).length);
}

async function fetchOnLeaveToday() {
    const pending = await safeGet('/api/leave-requests/pending', 'leave approvals');
    if (!pending.ok) return 0;
    const rows = pending.data?.data ?? [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return rows.filter((r) => {
        const start = new Date(r.start_date);
        const end = new Date(r.end_date);
        start.setHours(0, 0, 0, 0);
        end.setHours(0, 0, 0, 0);
        return r.status === 'pending' && start <= today && today <= end;
    }).length;
}


const stats = computed(() => [
    {
        title: 'Employees',
        value: state.loading ? '…' : state.metrics.employees,
        hint: 'Total employee records',
        icon: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.433-2.554M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
    },
    {
        title: 'On leave today',
        value: state.loading ? '…' : state.metrics.onLeaveToday,
        hint: 'Pending requests covering today',
        icon: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>',
    },
    {
        title: 'Pending approvals',
        value: state.loading ? '…' : state.metrics.pendingApprovals,
        hint: 'Leave requests awaiting action',
        icon: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    },
    {
        title: 'Open positions',
        value: state.loading ? '…' : state.metrics.openPositions,
        hint: 'Recruitment pipeline',
        icon: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-1.98-1.75-2.146a53.111 53.111 0 00-3.273-.424m0 0a48.098 48.098 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>',
    },
]);

/** Inline birthday row (keeps single-file scope small) */
const BirthdayCard = {
    name: 'BirthdayCard',
    props: {
        row: { type: Object, required: true },
        compact: { type: Boolean, default: false },
    },
    setup(props) {
        return () => {
            const b = props.row;
            const compact = props.compact;
            return h(
                'div',
                {
                    class: [
                        'flex items-center gap-3 rounded-2xl border bg-white/[0.03] px-4 py-3',
                        b.is_me ? 'border-emerald-500/40 ring-1 ring-emerald-500/20' : 'border-white/10',
                        compact ? 'py-2' : '',
                    ],
                },
                [
                    h(
                        'div',
                        {
                            class: compact
                                ? 'flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full border border-white/10 bg-white/10 text-xs font-semibold text-slate-300'
                                : 'flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full border border-white/10 bg-white/10 text-sm font-semibold text-slate-300',
                        },
                        b.profile_photo_url
                            ? [h('img', { src: b.profile_photo_url, alt: '', class: 'h-full w-full object-cover' })]
                            : [h('span', initials(b.full_name))]
                    ),
                    h('div', { class: 'min-w-0 flex-1' }, [
                        h('p', { class: 'truncate text-sm font-medium text-white' }, [
                            b.full_name,
                            b.is_me ? h('span', { class: 'ml-1 text-xs font-normal text-emerald-300' }, '(You)') : null,
                        ]),
                        h(
                            'p',
                            { class: 'truncate text-xs text-slate-500' },
                            [b.designation_name || '—', b.department_name ? ` · ${b.department_name}` : ''].join('')
                        ),
                    ]),
                    h('div', { class: 'shrink-0 text-right' }, [
                        b.is_today
                            ? h('span', { class: 'text-xs font-semibold text-amber-300' }, 'Today')
                            : h('span', { class: 'text-xs text-slate-400' }, [
                                  `in ${b.days_until} day${b.days_until === 1 ? '' : 's'}`,
                              ]),
                        h('p', { class: 'mt-0.5 text-[10px] text-slate-500' }, formatShortDate(b.next_birthday_on)),
                        !compact
                            ? h('p', { class: 'text-[10px] text-slate-500' }, `Turns ${b.turns_age}`)
                            : null,
                    ]),
                ]
            );
        };
    },
};

onMounted(async () => {
    state.loading = true;
    state.birthdaysError = '';
    state.hiddenMetrics = [];
    try {
        const meRes = await window.axios.get('/api/me');
        me.user = meRes.data.user ?? null;
        me.employee = meRes.data.employee ?? null;
        me.flags = meRes.data.flags ?? {};

        const hr = !!me.flags.hr_dashboard;

        const tasks = [
            window.axios.get('/api/announcements/feed').then((r) => {
                const rows = r.data?.data ?? [];
                feed.splice(0, feed.length, ...rows);
            }),
            window.axios
                .get('/api/company/birthdays-timeline')
                .then((r) => {
                    const rows = r.data?.data ?? [];
                    birthdays.splice(0, birthdays.length, ...rows);
                })
                .catch((e) => {
                    state.birthdaysError = e.response?.data?.message ?? 'Could not load birthdays.';
                }),
        ];

        if (hr) {
            tasks.push(
                (async () => {
                    const employeeCount = await fetchEmployeeCount();
                    const onLeaveToday = await fetchOnLeaveToday();
                    const pendingLeaves = await safeGet('/api/leave-requests/pending', 'leave approvals');
                    const jobPositions = await safeGet('/api/job-positions', 'recruitment');
                    state.metrics.employees = employeeCount;
                    state.metrics.onLeaveToday = onLeaveToday;
                    state.metrics.pendingApprovals = pendingLeaves.ok ? (pendingLeaves.data?.data ?? []).length : 0;
                    if (jobPositions.ok) {
                        state.metrics.openPositions = (jobPositions.data?.data ?? []).filter((r) => r.status === 'open')
                            .length;
                    }
                })()
            );
        }

        if (!hr && me.flags?.ess_payslip_view) {
            tasks.push(
                (async () => {
                    payslipLoading.value = true;
                    try {
                        const { data } = await window.axios.get('/api/my/payslips');
                        const rows = data?.data ?? [];
                        latestPayslip.value = rows[0] ?? null;
                    } catch {
                        latestPayslip.value = null;
                    } finally {
                        payslipLoading.value = false;
                    }
                })()
            );
        }

        if (!hr && me.flags?.ess_leave_apply && me.employee) {
            tasks.push(fetchMyLeave());
        }


        await Promise.all(tasks);

        const tick = () => {
            essTime.value = new Date().toLocaleTimeString(undefined, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        };
        tick();
        essTimer = setInterval(tick, 1000);
    } catch {
        /* ignore */
    } finally {
        state.loading = false;
    }
});

onUnmounted(() => {
    if (essTimer) {
        clearInterval(essTimer);
        essTimer = null;
    }
});
</script>
