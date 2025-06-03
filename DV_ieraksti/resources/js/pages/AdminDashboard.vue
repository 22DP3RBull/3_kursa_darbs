<script setup lang="js">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { LogOut, Plus, Filter, Sun, Moon } from 'lucide-vue-next';
import '../../css/admindashboard.css'; // Ensure this is imported for the admin dashboard

const tableMode = ref(1);
const stats = ref({
    studentsByFloor: [],
    checkedInCount: 0,
    checkedOutCount: 0,
});
const showLiveFilter = ref(false);
const showHistoryFilter = ref(false);
const searchQuery = ref('');
const selectedFloor = ref('');
const checkedInFilter = ref(null);
const students = ref([]);
const historyFilters = ref({
    search: '',
    action: '',
    date: '',
});
const historyData = ref([]);
const floors = [1, 2, 3, 4, 5];
const showAddStudentPopup = ref(false);
const newStudent = ref({
    name: '',
    surname: '',
    floor: '',
    room: '',
    phone: '',
    email: '',
});
const isDarkMode = ref(localStorage.getItem('isDarkMode') === 'true');
const currentPage = ref(1);
const itemsPerPage = 25;

const paginatedStudents = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return students.value.slice(start, end);
});

const paginatedHistory = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return historyData.value.slice(start, end);
});

const totalStudentPages = computed(() => Math.ceil(students.value.length / itemsPerPage));
const totalHistoryPages = computed(() => Math.ceil(historyData.value.length / itemsPerPage));

const fetchDashboardStats = async () => {
    const response = await axios.get('/dashboard-stats');
    stats.value = response.data;
};

const fetchStudents = async () => {
    const response = await axios.get('/students');
    students.value = response.data;
    currentPage.value = 1; // Reset to first page on new data
};

const fetchHistory = async () => {
    try {
        const response = await axios.get('/history');
        historyData.value = response.data;
    } catch (error) {
        console.error('Error fetching history:', error);
    }
};

const toggleLiveFilter = () => {
    showLiveFilter.value = !showLiveFilter.value;
};

const toggleHistoryFilter = () => {
    showHistoryFilter.value = !showHistoryFilter.value;
};

const applyFilters = () => {
    fetchStudents();
};

const applyHistoryFilters = () => {
    fetchHistory();
};

const setTableMode = (mode) => {
    tableMode.value = mode;
    if (mode === 1) {
        fetchStudents();
    } else if (mode === 2) {
        fetchHistory();
    }
};

const toggleFooterDim = (dim) => {
    const footer = document.querySelector('.footer');
    if (footer) {
        footer.classList.toggle('dimmed', dim);
    }
};

const openAddStudentPopup = () => {
    showAddStudentPopup.value = true;
    toggleFooterDim(true);
};

const closeAddStudentPopup = () => {
    showAddStudentPopup.value = false;
    toggleFooterDim(false);
};

const addStudent = async () => {
    try {
        const response = await axios.post('/students', newStudent.value);
        students.value.push(response.data);
        closeAddStudentPopup();
        fetchDashboardStats();
    } catch (error) {
        console.error('Error adding student:', error);
        alert('Failed to add student. Please check the input and try again.');
    }
};

const logout = () => {
    axios.post('/logout').then(() => {
        window.location.href = '/';
    });
};

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem('isDarkMode', isDarkMode.value);
};

const calculateTimeSince = (student) => {
    const lastActionTime = student.checkedIn ? student.last_check_in : student.last_check_out;
    if (!lastActionTime) return 'N/A';
    const diff = new Date() - new Date(lastActionTime);
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
    return `${days} days, ${hours} hours`;
};

const openEditStudentPopup = (student) => {
    console.log('Edit student:', student);
};

const openDeletePopup = (student) => {
    console.log('Delete student:', student);
};

const changePage = (page) => {
    if (page >= 1 && page <= (tableMode.value === 1 ? totalStudentPages.value : totalHistoryPages.value)) {
        currentPage.value = page;
    }
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedFloor.value = '';
    checkedInFilter.value = null;
    applyFilters();
};

const closePopupOnOutsideClick = (event, popupRef, closeFunction) => {
    if (popupRef.value && !popupRef.value.contains(event.target)) {
        closeFunction();
    }
};

onMounted(() => {
    fetchDashboardStats();
    fetchStudents();
    fetchHistory();
});
</script>

<template>
  <div :class="{ 'dark-mode': isDarkMode }" class="admin-dashboard">
    <!-- Navbar -->
    <nav class="navbar">
      <div class="navbar-left">
        <h1>Dienesta viesnīcas ieraksti</h1>
      </div>
      <div class="navbar-right">
        <button class="nav-button" @click="logout">
          <LogOut class="w-5 h-5 mr-2 text-white" />
          Logout
        </button>
        <button class="nav-button" @click="openAddStudentPopup">
          <Plus class="w-5 h-5 mr-2 text-white" />
          Add Student
        </button>
      </div>
    </nav>

    <!-- Content -->
    <div class="content">
      <!-- Stats Card -->
      <div class="stats-card">
        <div class="stats-content">
          <h2>Dashboard Overview</h2>
          <div class="stats-grid">
            <div v-for="floor in stats.studentsByFloor" :key="floor.floor" class="stat-item">
              <p>Floor {{ floor.floor }}</p>
              <p>{{ floor.total_students }} Students</p>
            </div>
            <div class="stat-item">
              <p>Checked In</p>
              <p>{{ stats.checkedInCount }} Students</p>
            </div>
            <div class="stat-item">
              <p>Checked Out</p>
              <p>{{ stats.checkedOutCount }} Students</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Mode Switcher -->
      <div class="table-mode-switcher">
        <button @click="setTableMode(1)" :class="{ active: tableMode === 1 }">Live Filters</button>
        <button @click="setTableMode(2)" :class="{ active: tableMode === 2 }">History</button>
      </div>

      <!-- Live Filters Mode -->
      <div v-if="tableMode === 1">
        <!-- Filter Toggle -->
        <div :class="['filter-toggle', { 'slide-out': showLiveFilter }]" @click="toggleLiveFilter">
          <Filter class="w-6 h-6 text-white" />
        </div>

        <!-- Filter Card -->
        <div :class="['filter-card', { 'slide-out': showLiveFilter }]">
          <h2>Filter Students</h2>
          <input v-model="searchQuery" placeholder="Search by name, surname, or room number" class="input" @input="applyFilters" />
          <div>
            <label class="label">Floor:</label>
            <div class="floor-buttons">
              <button v-for="floor in floors" :key="floor" @click="selectedFloor = floor" :class="{ active: selectedFloor === floor }">
                {{ floor }}
              </button>
            </div>
          </div>
          <div>
            <label class="label">Checked In Status:</label>
            <div class="status-buttons">
              <button @click="checkedInFilter = true" :class="{ active: checkedInFilter === true }">Checked In</button>
              <button @click="checkedInFilter = false" :class="{ active: checkedInFilter === false }">Not Checked In</button>
            </div>
          </div>
          <button @click="resetFilters" class="close-filter-button">Reset Filters</button>
        </div>

        <!-- Students Table -->
        <div :class="{ 'table-adjusted': showLiveFilter }">
          <table class="students-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Surname</th>
                <th>Floor</th>
                <th>Room</th>
                <th>Phone Number</th>
                <th>Checked In</th>
                <th>Time Since Last Action</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in paginatedStudents" :key="student.id">
                <td>{{ student.name }}</td>
                <td>{{ student.surname }}</td>
                <td>{{ student.floor }}</td>
                <td>{{ student.room }}</td>
                <td>{{ student.phone }}</td>
                <td>
                  <span :class="['status-circle', student.checkedIn ? 'green' : 'red']"></span>
                </td>
                <td>{{ calculateTimeSince(student) }}</td>
                <td>
                  <button class="edit-button" @click="openEditStudentPopup(student)">Edit</button>
                  <button class="delete-button" @click="openDeletePopup(student)">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
          <!-- Pagination -->
          <div class="pagination">
            <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1" class="pagination-button">Previous</button>
            <span v-for="page in totalStudentPages" :key="page">
              <button @click="changePage(page)" :class="{ 'active': currentPage === page }" class="pagination-button">{{ page }}</button>
            </span>
            <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalStudentPages" class="pagination-button">Next</button>
          </div>
        </div>
      </div>

      <!-- History Mode -->
      <div v-else-if="tableMode === 2">
        <!-- History Filter Toggle -->
        <div :class="['filter-toggle', { 'slide-out': showHistoryFilter }]" @click="toggleHistoryFilter">
          <Filter class="w-6 h-6 text-white" />
        </div>

        <!-- History Filter Card -->
        <div :class="['filter-card', { 'slide-out': showHistoryFilter }]">
          <h2>Filter History</h2>
          <input v-model="historyFilters.search" placeholder="Search by name, surname, or room number" />
          <div>
            <label>Action:</label>
            <select v-model="historyFilters.action">
              <option value="">All</option>
              <option value="check-in">Check-In</option>
              <option value="check-out">Check-Out</option>
            </select>
          </div>
          <div>
            <label>Date:</label>
            <input type="date" v-model="historyFilters.date" />
          </div>
          <button @click="applyHistoryFilters" class="filter-button">Apply Filters</button>
        </div>

        <!-- History Table -->
        <table class="students-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Surname</th>
              <th>Room</th>
              <th>Floor</th>
              <th>Action</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in paginatedHistory" :key="record.id">
              <td>{{ record.name }}</td>
              <td>{{ record.surname }}</td>
              <td>{{ record.room }}</td>
              <td>{{ record.floor }}</td>
              <td>{{ record.action }}</td>
              <td>{{ new Date(record.performed_at).toLocaleDateString() }}</td>
            </tr>
          </tbody>
        </table>
        <!-- Pagination -->
        <div class="pagination">
          <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1" class="pagination-button">Previous</button>
          <span v-for="page in totalHistoryPages" :key="page">
            <button @click="changePage(page)" :class="{ 'active': currentPage === page }" class="pagination-button">{{ page }}</button>
          </span>
          <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalHistoryPages" class="pagination-button">Next</button>
        </div>
      </div>

      <!-- Add Student Popup -->
      <div v-if="showAddStudentPopup" class="popup-overlay" @click.self="closeAddStudentPopup">
        <div class="popup-card" ref="addStudentPopup">
          <h2>Add Student</h2>
          <form @submit.prevent="addStudent" class="grid gap-4">
            <div>
              <label class="label">Name:</label>
              <input v-model="newStudent.name" required />
            </div>
            <div>
              <label class="label">Surname:</label>
              <input v-model="newStudent.surname" required />
            </div>
            <div>
              <label class="label">Floor:</label>
              <select v-model="newStudent.floor" required>
                <option value="" disabled>Select Floor</option>
                <option v-for="floor in floors" :key="floor" :value="floor">{{ floor }}</option>
              </select>
            </div>
            <div>
              <label class="label">Room:</label>
              <input v-model="newStudent.room" required />
            </div>
            <div>
              <label class="label">Phone:</label>
              <input v-model="newStudent.phone" required />
            </div>
            <div>
              <label class="label">Email:</label>
              <input v-model="newStudent.email" required />
            </div>
            <div class="popup-actions">
              <button type="submit" class="confirm-button">Submit</button>
              <button type="button" @click="closeAddStudentPopup" class="cancel-button">Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Student Popup -->
      <div v-if="showEditStudentPopup" class="popup-overlay" @click.self="closeEditStudentPopup">
        <div class="popup-card">
          <h2>Edit Student</h2>
          <!-- Add edit form content here -->
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
      <button @click="toggleDarkMode" class="theme-toggle">
        <Sun v-if="isDarkMode" class="text-white w-6 h-6" />
        <Moon v-else class="text-white w-6 h-6" />
      </button>
      <div>
        Contact us at: <a href="mailto:info@example.com" class="text-[#1abc9c] hover:underline">info@example.com</a>
      </div>
    </footer>
  </div>
</template>