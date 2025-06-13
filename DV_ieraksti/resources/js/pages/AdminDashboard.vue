<script setup lang="js">
import { ref, onMounted, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { LogOut, Plus, Filter, Sun, Moon, ArrowDownUp } from 'lucide-vue-next';
import '../../css/admindashboard.css';

// Only reload if not already loaded in this session
if (typeof window !== 'undefined' && window.innerWidth <= 900 && !sessionStorage.getItem('admin-dashboard-loaded')) {
    sessionStorage.setItem('admin-dashboard-loaded', '1');
    window.location.reload();
}

const loading = ref(true);
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
const showEditStudentPopup = ref(false);
const newStudent = ref({
    name: '',
    surname: '',
    floor: '',
    room: '',
    phone: '',
    email: '',
});
const editStudent = ref(null);
const editValidationErrors = ref({});
const isDarkMode = ref(localStorage.getItem('isDarkMode') === 'true');
const validationErrors = ref({});

const studentCurrentPage = ref(1);
const historyCurrentPage = ref(1);
const itemsPerPage = 25;

const page = usePage();
const adminName = computed(() => page.props.auth?.user?.name || 'Admin');
const showWelcomePopup = ref(false);

// --- SORTING LOGIC (move this block to the top, before setSortDropdown and watcher) ---
const sortOptions = [
  { key: 'name-asc', label: 'Name (A-Z)', field: 'name', direction: 'asc' },
  { key: 'name-desc', label: 'Name (Z-A)', field: 'name', direction: 'desc' },
  { key: 'surname-asc', label: 'Surname (A-Z)', field: 'surname', direction: 'asc' },
  { key: 'surname-desc', label: 'Surname (Z-A)', field: 'surname', direction: 'desc' },
  { key: 'floor-asc', label: 'Floor (Ascending)', field: 'floor', direction: 'asc' },
  { key: 'floor-desc', label: 'Floor (Descending)', field: 'floor', direction: 'desc' },
  { key: 'room-asc', label: 'Room (Ascending)', field: 'room', direction: 'asc' },
  { key: 'room-desc', label: 'Room (Descending)', field: 'room', direction: 'desc' },
  { key: 'checkedIn-desc', label: 'Checked In First', field: 'checkedIn', direction: 'desc' },
  { key: 'checkedIn-asc', label: 'Checked Out First', field: 'checkedIn', direction: 'asc' },
  { key: 'lastAction-asc', label: 'Time Since Last Action (Oldest First)', field: 'lastAction', direction: 'asc' },
  { key: 'lastAction-desc', label: 'Time Since Last Action (Newest First)', field: 'lastAction', direction: 'desc' },
];

const sortField = ref('name');
const sortDirection = ref('asc');
const selectedSortKey = ref(sortOptions[0].key);

const setSortDropdown = (key) => {
  const option = sortOptions.find(opt => opt.key === key);
  if (!option) return;
  sortField.value = option.field;
  sortDirection.value = option.direction;
  fetchStudents();
};

const fetchStudents = async () => {
  const params = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (selectedFloor.value) params.floor = selectedFloor.value;
  if (checkedInFilter.value !== null) params.checkedIn = checkedInFilter.value;
  // Always send sort params except for lastAction (handled client-side)
  if (sortField.value !== 'lastAction') {
    params.field = sortField.value;
    params.direction = sortDirection.value;
  }
  const response = await axios.get('/students', { params });
  students.value = response.data;
  studentCurrentPage.value = 1;
};

const sortedStudents = computed(() => {
  if (sortField.value === 'lastAction') {
    let arr = [...students.value];
    arr.sort((a, b) => {
      const aTime = new Date(a.checkedIn ? a.last_check_in : a.last_check_out).getTime() || 0;
      const bTime = new Date(b.checkedIn ? b.last_check_in : b.last_check_out).getTime() || 0;
      return sortDirection.value === 'asc' ? aTime - bTime : bTime - aTime;
    });
    return arr;
  }
  return students.value;
});

const paginatedStudents = computed(() => {
  const arr = sortField.value === 'lastAction' ? sortedStudents.value : students.value;
  const start = (studentCurrentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return arr.slice(start, end);
});
const paginatedHistory = computed(() => {
    const start = (historyCurrentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return historyData.value.slice(start, end);
});
const totalStudentPages = computed(() => Math.ceil(students.value.length / itemsPerPage));
const totalHistoryPages = computed(() => Math.ceil(historyData.value.length / itemsPerPage));

const fetchDashboardStats = async () => {
    const response = await axios.get('/dashboard-stats');
    stats.value = response.data;
};

const fetchHistory = async () => {
    const params = {};
    if (historyFilters.value.search) params.search = historyFilters.value.search;
    if (historyFilters.value.action) params.action = historyFilters.value.action;
    if (historyFilters.value.date) params.date = historyFilters.value.date;
    const response = await axios.get('/history', { params });
    historyData.value = response.data;
    historyCurrentPage.value = 1;
};

watch([searchQuery, selectedFloor, checkedInFilter], () => {
    if (tableMode.value === 1) fetchStudents();
});
watch([() => historyFilters.value.search, () => historyFilters.value.action, () => historyFilters.value.date], () => {
    if (tableMode.value === 2) fetchHistory();
});

const setTableMode = (mode) => {
    tableMode.value = mode;
    if (mode === 1) {
        fetchStudents();
        studentCurrentPage.value = 1;
    } else if (mode === 2) {
        fetchHistory();
        historyCurrentPage.value = 1;
    }
};

const changeStudentPage = (page) => {
    if (page >= 1 && page <= totalStudentPages.value) {
        studentCurrentPage.value = page;
    }
};
const changeHistoryPage = (page) => {
    if (page >= 1 && page <= totalHistoryPages.value) {
        historyCurrentPage.value = page;
    }
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedFloor.value = '';
    checkedInFilter.value = null;
    fetchStudents();
};
const resetHistoryFilters = () => {
    historyFilters.value.search = '';
    historyFilters.value.action = '';
    historyFilters.value.date = '';
    fetchHistory();
};

const toggleLiveFilter = () => {
    showLiveFilter.value = !showLiveFilter.value;
    if (showLiveFilter.value) showHistoryFilter.value = false;
};
const toggleHistoryFilter = () => {
    showHistoryFilter.value = !showHistoryFilter.value;
    if (showHistoryFilter.value) showLiveFilter.value = false;
};

const setDarkModeClass = () => {
    if (isDarkMode.value) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }
};

onMounted(() => {
    loading.value = true;
    setTimeout(() => {
        loading.value = false;
    }, 2000);
    setDarkModeClass();
    fetchDashboardStats();
    fetchStudents();
    fetchHistory();
});

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem('isDarkMode', isDarkMode.value);
    setDarkModeClass();
};

function validateStudentInput(student) {
  const errors = {};
  // Latvian letters, spaces, hyphens, apostrophes
  const nameRegex = /^[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž\s'\-]+$/u;
  const phoneRegex = /^[0-9]{8}$/;
  const emailRegex = /^[^@]+@rvt\.lv$/;
  if (!student.name || !nameRegex.test(student.name)) {
    errors.name = 'Name must contain only letters (including Latvian) and no special characters.';
  }
  if (!student.surname || !nameRegex.test(student.surname)) {
    errors.surname = 'Surname must contain only letters (including Latvian) and no special characters.';
  }
  if (!student.email || !emailRegex.test(student.email)) {
    errors.email = 'Email must end with @rvt.lv.';
  }
  if (!student.phone || !phoneRegex.test(student.phone)) {
    errors.phone = 'Phone must be exactly 8 digits.';
  }
  if (!student.floor || student.floor < 1 || student.floor > 5) {
    errors.floor = 'Floor must be between 1 and 5.';
  }
  if (!student.room || !isValidRoomForFloor(student.room, student.floor)) {
    errors.room = 'Room is not valid for the selected floor.';
  }
  return errors;
}

function isValidRoomForFloor(room, floor) {
  const ranges = {
    1: [101, 128],
    2: [201, 228],
    3: [301, 328],
    4: [401, 428],
    5: [501, 528],
  };
  if (!ranges[floor]) return false;
  return room >= ranges[floor][0] && room <= ranges[floor][1];
}

// Live validation for add student
watch(newStudent, (val) => {
  validationErrors.value = validateStudentInput(val);
}, { deep: true });

const addStudent = async () => {
  validationErrors.value = validateStudentInput(newStudent.value);
  if (Object.keys(validationErrors.value).length > 0) return;
  try {
    const response = await axios.post('/students', newStudent.value);
    students.value.push(response.data);
    closeAddStudentPopup();
    fetchDashboardStats();
    fetchStudents();
  } catch (error) {
    if (error.response && error.response.data.errors) {
      validationErrors.value = error.response.data.errors;
    } else {
      alert('Failed to add student. Please check the input and try again.');
    }
  }
};

// --- EDIT STUDENT ---
const openEditStudentPopup = (student) => {
    editStudent.value = { ...student };
    editValidationErrors.value = {};
    showEditStudentPopup.value = true;
    toggleFooterDim(true);
};

const closeEditStudentPopup = () => {
    showEditStudentPopup.value = false;
    toggleFooterDim(false);
};

watch(editStudent, (val) => {
  if (val) editValidationErrors.value = validateStudentInput(val);
}, { deep: true });

const updateStudent = async () => {
  editValidationErrors.value = validateStudentInput(editStudent.value);
  if (Object.keys(editValidationErrors.value).length > 0) return;
  try {
    const response = await axios.put(`/students/${editStudent.value.id}`, editStudent.value);
    // Update the student in the students array
    const idx = students.value.findIndex(s => s.id === editStudent.value.id);
    if (idx !== -1) students.value[idx] = response.data;
    closeEditStudentPopup();
    fetchDashboardStats();
    fetchStudents();
    // Do not show alert here, update succeeded
  } catch (error) {
    if (error.response && error.response.data.errors) {
      editValidationErrors.value = error.response.data.errors;
    } else {
      alert('Failed to update student. Please check the input and try again.');
    }
  }
};
// --- END EDIT STUDENT ---

const logout = () => {
    axios.post('/logout').then(() => {
        window.location.href = '/';
    });
};

const calculateTimeSince = (student) => {
    const lastActionTime = student.checkedIn ? student.last_check_in : student.last_check_out;
    if (!lastActionTime) return 'N/A';
    const diff = new Date() - new Date(lastActionTime);
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
    return `${days} days, ${hours} hours`;
};

const openDeletePopup = (student) => {
    studentToDelete.value = student;
    showDeletePopup.value = true;
};

const closePopupOnOutsideClick = (event, popupRef, closeFunction) => {
    if (popupRef.value && !popupRef.value.contains(event.target)) {
        closeFunction();
    }
};

const openAddStudentPopup = () => {
    showAddStudentPopup.value = true;
};
const closeAddStudentPopup = () => {
    showAddStudentPopup.value = false;
};

const studentToDelete = ref(null);
const showDeletePopup = ref(false);

const confirmDelete = (student) => {
    studentToDelete.value = student;
    showDeletePopup.value = true;
};

const closeDeletePopup = () => {
    showDeletePopup.value = false;
    studentToDelete.value = null;
};

const deleteStudent = async () => {
    if (!studentToDelete.value) return;
    try {
        await axios.delete(`/students/${studentToDelete.value.id}`);
        closeDeletePopup();
        await fetchDashboardStats();
        await fetchStudents();
    } catch (error) {
        alert('Failed to delete student. Please try again.');
    }
};

const closeWelcomePopup = () => {
    showWelcomePopup.value = false;
};

// Floor filter toggle logic
function toggleFloorFilter(floor) {
  selectedFloor.value = selectedFloor.value === floor ? '' : floor;
  fetchStudents();
}

// CheckedIn filter logic
function setCheckedInFilter(val) {
  checkedInFilter.value = val;
  fetchStudents();
}
</script>

<template>
  <div :class="{ 'dark-mode': isDarkMode }" class="admin-dashboard" style="min-height:100vh;display:flex;flex-direction:column;">
    <div v-if="loading" class="admin-loading-skeleton-overlay">
      <div class="admin-loading-skeleton-card">
        <div class="admin-loading-skeleton-title"></div>
        <div class="admin-loading-skeleton-row"></div>
        <div class="admin-loading-skeleton-row"></div>
        <div class="admin-loading-skeleton-row"></div>
        <div class="admin-loading-skeleton-row"></div>
      </div>
    </div>
    <template v-if="!loading">
      <nav class="navbar" style="position:static;z-index:auto;">
        <div class="navbar-left">
          <h1>Dienesta viesnīcas ieraksti</h1>
        </div>
        <div class="navbar-right">
          <button class="nav-icon-button" @click="logout">
            <span class="icon-wrap">
              <LogOut class="icon" />
              <span class="btn-text">Logout</span>
            </span>
          </button>
          <button class="nav-icon-button" @click="openAddStudentPopup">
            <span class="icon-wrap">
              <Plus class="icon" />
              <span class="btn-text">Add Student</span>
            </span>
          </button>
        </div>
      </nav>
      <div class="content" style="flex:1 0 auto;">
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
          <button @click="setTableMode(1)" :class="{ active: tableMode === 1 }">Students</button>
          <button @click="setTableMode(2)" :class="{ active: tableMode === 2 }">History</button>
        </div>

        <!-- Students Table Mode -->
        <div v-if="tableMode === 1">
          <!-- Filter Toggle -->
          <div :class="['filter-toggle', { 'slide-out': showLiveFilter }]" @click="toggleLiveFilter">
            <Filter class="w-6 h-6 text-white" />
          </div>

          <!-- Filter Card -->
          <div :class="['filter-card', { 'slide-out': showLiveFilter }]">
            <h2>Filter Students</h2>
            <input v-model="searchQuery" placeholder="Search by name, surname, or room number" class="input" />
            <div>
              <label class="label">Floor:</label>
              <div class="floor-buttons" @click.stop>
                <button
                  v-for="floor in floors"
                  :key="floor"
                  type="button"
                  @mousedown.prevent="toggleFloorFilter(floor)"
                  :class="{ active: selectedFloor === floor }"
                  tabindex="0"
                >
                  {{ floor }}
                </button>
              </div>
            </div>
            <div>
              <label class="label">Checked In Status:</label>
              <div class="status-buttons" @click.stop>
                <button type="button" @mousedown.prevent="setCheckedInFilter(true)" :class="{ active: checkedInFilter === true }" tabindex="0">Checked In</button>
                <button type="button" @mousedown.prevent="setCheckedInFilter(false)" :class="{ active: checkedInFilter === false }" tabindex="0">Not Checked In</button>
                <button type="button" @mousedown.prevent="setCheckedInFilter(null)" :class="{ active: checkedInFilter === null }" tabindex="0">All</button>
              </div>
            </div>
            <button type="button" @mousedown.prevent="resetFilters" class="close-filter-button" tabindex="0">Reset Filters</button>
          </div>

          <!-- Sort Dropdown (right aligned) -->
          <div class="student-table-header-row">
            <div style="flex:1"></div>
            <div class="sort-dropdown-wrap sort-dropdown-right">
              <button class="sort-dropdown-btn" type="button" tabindex="-1">
                <ArrowDownUp style="width:18px;height:18px;vertical-align:middle;margin-right:4px;" />
                Sort
              </button>
              <select
                class="sort-dropdown-select"
                v-model="selectedSortKey"
                @change="setSortDropdown($event.target.value)"
              >
                <option v-for="option in sortOptions" :key="option.key" :value="option.key">
                  {{ option.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Students Table -->
          <div class="table-container" style="position:static !important;">
            <div :class="{ 'table-adjusted': showLiveFilter }" style="position:static !important;">
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
                      <span
                        class="status-circle"
                        :class="student.checkedIn ? 'green' : 'red'"
                        :title="student.checkedIn ? 'Checked In' : 'Checked Out'"
                      ></span>
                    </td>
                    <td>{{ calculateTimeSince(student) }}</td>
                    <td>
                      <button class="table-action-btn edit" @click="openEditStudentPopup(student)">Edit</button>
                      <button class="table-action-btn delete" @click="openDeletePopup(student)">Delete</button>
                    </td>
                  </tr>
                </tbody>
              </table>
              <!-- Pagination -->
              <div class="pagination">
                <button @click="changeStudentPage(studentCurrentPage - 1)" :disabled="studentCurrentPage === 1" class="pagination-button">Previous</button>
                <span v-for="page in totalStudentPages" :key="page">
                  <button @click="changeStudentPage(page)" :class="{ 'active': studentCurrentPage === page }" class="pagination-button">{{ page }}</button>
                </span>
                <button @click="changeStudentPage(studentCurrentPage + 1)" :disabled="studentCurrentPage === totalStudentPages" class="pagination-button">Next</button>
              </div>
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
            <button @click="resetHistoryFilters" class="close-filter-button">Reset Filters</button>
          </div>

          <!-- History Table -->
          <div :class="{ 'table-adjusted': showHistoryFilter }">
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
              <button @click="changeHistoryPage(historyCurrentPage - 1)" :disabled="historyCurrentPage === 1" class="pagination-button">Previous</button>
              <span v-for="page in totalHistoryPages" :key="page">
                <button @click="changeHistoryPage(page)" :class="{ 'active': historyCurrentPage === page }" class="pagination-button">{{ page }}</button>
              </span>
              <button @click="changeHistoryPage(historyCurrentPage + 1)" :disabled="historyCurrentPage === totalHistoryPages" class="pagination-button">Next</button>
            </div>
          </div>
        </div>

        <!-- Add Student Popup -->
        <div v-if="showAddStudentPopup" class="popup-overlay" @click.self="closeAddStudentPopup">
          <div class="popup-card popup-card-bg" ref="addStudentPopup">
            <h2>Add Student</h2>
            <form @submit.prevent="addStudent" class="grid gap-4">
              <div>
                <label class="label">Name:</label>
                <input v-model="newStudent.name" required />
                <span v-if="validationErrors.name" class="input-error">{{ validationErrors.name }}</span>
              </div>
              <div>
                <label class="label">Surname:</label>
                <input v-model="newStudent.surname" required />
                <span v-if="validationErrors.surname" class="input-error">{{ validationErrors.surname }}</span>
              </div>
              <div>
                <label class="label">Floor:</label>
                <select v-model="newStudent.floor" required>
                  <option value="" disabled>Select Floor</option>
                  <option v-for="floor in floors" :key="floor" :value="floor">{{ floor }}</option>
                </select>
                <span v-if="validationErrors.floor" class="input-error">{{ validationErrors.floor }}</span>
              </div>
              <div>
                <label class="label">Room:</label>
                <input v-model="newStudent.room" required />
                <span v-if="validationErrors.room" class="input-error">{{ validationErrors.room }}</span>
              </div>
              <div>
                <label class="label">Phone:</label>
                <input v-model="newStudent.phone" required />
                <span v-if="validationErrors.phone" class="input-error">{{ validationErrors.phone }}</span>
              </div>
              <div>
                <label class="label">Email:</label>
                <input v-model="newStudent.email" required />
                <span v-if="validationErrors.email" class="input-error">{{ validationErrors.email }}</span>
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
          <div class="popup-card popup-card-bg">
            <h2>Edit Student</h2>
            <form @submit.prevent="updateStudent" class="grid gap-4">
              <div>
                <label class="label">Name:</label>
                <input v-model="editStudent.name" required />
                <span v-if="editValidationErrors.name" class="input-error">{{ editValidationErrors.name }}</span>
              </div>
              <div>
                <label class="label">Surname:</label>
                <input v-model="editStudent.surname" required />
                <span v-if="editValidationErrors.surname" class="input-error">{{ editValidationErrors.surname }}</span>
              </div>
              <div>
                <label class="label">Floor:</label>
                <select v-model="editStudent.floor" required>
                  <option value="" disabled>Select Floor</option>
                  <option v-for="floor in floors" :key="floor" :value="floor">{{ floor }}</option>
                </select>
                <span v-if="editValidationErrors.floor" class="input-error">{{ editValidationErrors.floor }}</span>
              </div>
              <div>
                <label class="label">Room:</label>
                <input v-model="editStudent.room" required />
                <span v-if="editValidationErrors.room" class="input-error">{{ editValidationErrors.room }}</span>
              </div>
              <div>
                <label class="label">Phone:</label>
                <input v-model="editStudent.phone" required />
                <span v-if="editValidationErrors.phone" class="input-error">{{ editValidationErrors.phone }}</span>
              </div>
              <div>
                <label class="label">Email:</label>
                <input v-model="editStudent.email" required />
                <span v-if="editValidationErrors.email" class="input-error">{{ editValidationErrors.email }}</span>
              </div>
              <div class="popup-actions">
                <button type="submit" class="confirm-button">Save</button>
                <button type="button" @click="closeEditStudentPopup" class="cancel-button">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Delete Confirmation Popup -->
        <div v-if="showDeletePopup" class="popup-overlay" @click.self="closeDeletePopup">
          <div class="popup-card">
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete this student?</p>
            <div class="popup-actions">
              <button @click="deleteStudent" class="confirm-button">Yes, Delete</button>
              <button @click="closeDeletePopup" class="cancel-button">Cancel</button>
            </div>
          </div>
        </div>
      </div>
      
      <footer class="footer fixed-footer">
        <div class="footer-left">
          <button @click="toggleDarkMode" class="theme-toggle">
            <Sun v-if="isDarkMode" class="text-white w-6 h-6" />
            <Moon v-else class="text-white w-6 h-6" />
          </button>
        </div>
        <div class="footer-center">
          Contact us at: <a href="mailto:info@rvt.lv" class="login-icon">info@rvt.lv</a>
        </div>
      </footer>
    </template>
  </div>
</template>

<style>
/* Remove scoped to ensure only one style is used and avoid specificity/overlay issues */
/* ...existing code from admindashboard.css... */

/* Make footer fixed to the bottom of the page, always under popups */
.footer.fixed-footer {
  position: static;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100vw;
  margin: 0;
  border-radius: 0;
  z-index: 1; /* Lower than popup-overlay */
  background: var(--footer-background, #fff);
  box-shadow: 0 -2px 12px rgba(0,0,0,0.08);
  padding: 14px 0;
}

/* Ensure popup overlays are always above everything including footer */
.popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 3000;
  overflow-y: auto;
}

/* Make sure .navbar is not fixed and does not overlay popups */
.navbar {
  position: static !important;
  z-index: 1 !important;
}

/* Add enough bottom padding to .content so footer never overlaps content */
.content {
  padding-bottom: 120px;
  flex: 1 0 auto;
}

/* Make popup actions (submit/cancel) buttons higher on the card for add/edit student */
.popup-card .popup-actions {
  display: flex;
  justify-content: space-around;
  margin-top: 10px;      /* Reduced from 20px */
  margin-bottom: 40px;   /* Add extra bottom margin to lift buttons above footer */
}

/* On small screens, increase margin even more */
@media (max-width: 900px) {
  .popup-card .popup-actions {
    margin-bottom: 60px;
  }
}
</style>