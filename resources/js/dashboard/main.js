import '../bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import DashboardHome from './pages/DashboardHome.vue';
import DashboardReports from './pages/DashboardReports.vue';
import EmployeesIndex from './pages/EmployeesIndex.vue';
import EmployeeCreate from './pages/EmployeeCreate.vue';
import EmployeeShow from './pages/EmployeeShow.vue';
import EmployeeEdit from './pages/EmployeeEdit.vue';
import AttendanceIndex from './pages/AttendanceIndex.vue';
import AttendanceEmployeeMonth from './pages/AttendanceEmployeeMonth.vue';
import EssMyLeave from './pages/EssMyLeave.vue';
import LeaveApprovals from './pages/LeaveApprovals.vue';
import PayrollRuns from './pages/PayrollRuns.vue';
import MyPayslips from './pages/MyPayslips.vue';
import RecruitmentPositions from './pages/RecruitmentPositions.vue';
import RecruitmentPositionApplications from './pages/RecruitmentPositionApplications.vue';
import RecruitmentPositionDetail from './pages/RecruitmentPositionDetail.vue';
import RecruitmentPositionEdit from './pages/RecruitmentPositionEdit.vue';
import AnnouncementsIndex from './pages/AnnouncementsIndex.vue';
import EssAnnouncements from './pages/EssAnnouncements.vue';

const router = createRouter({
    history: createWebHistory('/dashboard'),
    routes: [
        { path: '/', name: 'dashboard.home', component: DashboardHome },
        { path: '/employees/create', name: 'employees.create', component: EmployeeCreate },
        { path: '/employees/:id/edit', name: 'employees.edit', component: EmployeeEdit },
        { path: '/employees/:id', name: 'employees.show', component: EmployeeShow },
        { path: '/employees', name: 'employees.index', component: EmployeesIndex },
        { path: '/attendance/:employeeId', name: 'attendance.employee-month', component: AttendanceEmployeeMonth },
        { path: '/my/attendance', name: 'ess.my-attendance', component: AttendanceEmployeeMonth, meta: { essSelf: true } },
        { path: '/attendance', name: 'attendance.index', component: AttendanceIndex },
        { path: '/my/leave', name: 'ess.my-leave', component: EssMyLeave },
        { path: '/leave-approvals', name: 'leave.approvals', component: LeaveApprovals },
        { path: '/payroll', name: 'payroll.runs', component: PayrollRuns },
        { path: '/recruitment/:positionId/applications', name: 'recruitment.position-applications', component: RecruitmentPositionApplications },
        { path: '/recruitment/:positionId/edit', name: 'recruitment.position-edit', component: RecruitmentPositionEdit },
        { path: '/recruitment/:positionId', name: 'recruitment.position-detail', component: RecruitmentPositionDetail },
        { path: '/recruitment', name: 'recruitment.positions', component: RecruitmentPositions },
        { path: '/announcements', name: 'announcements.index', component: AnnouncementsIndex },
        { path: '/my/payslips', name: 'ess.my-payslips', component: MyPayslips },
        { path: '/my/announcements', name: 'ess.my-announcements', component: EssAnnouncements },
        { path: '/reports', name: 'dashboard.reports', component: DashboardReports },
    ],
});

createApp(App).use(router).mount('#dashboard-app');
