<script setup lang="js">
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { Sun, Moon } from 'lucide-vue-next';
import '../../css/mainpage.css';

const floors = ref([1, 2, 3, 4, 5]);
const rooms = ref([]);
const students = ref([]);
const selectedFloor = ref('');
const selectedRoom = ref('');
const selectedStudent = ref(null);
const showCheckInPopup = ref(false);
const showCheckOutPopup = ref(false);
const isDarkMode = ref(localStorage.getItem('isDarkMode') === 'true');

// Fetch rooms for the selected floor only
const fetchRooms = async (floor = null) => {
  try {
    let url = '/rooms';
    if (floor) url += `?floor=${floor}`;
    const response = await axios.get(url);
    rooms.value = response.data;
  } catch (error) {
    console.error('Error fetching rooms:', error);
  }
};

const fetchStudents = async () => {
  try {
    const response = await axios.get('/students');
    students.value = response.data;
  } catch (error) {
    console.error('Error fetching students:', error);
  }
};

onMounted(() => {
  if (isDarkMode.value) {
    document.body.classList.add('dark-mode');
  } else {
    document.body.classList.remove('dark-mode');
  }
  fetchStudents();
});

// Watch for floor selection to fetch rooms for that floor
watch(selectedFloor, (floor) => {
  selectedRoom.value = '';
  selectedStudent.value = null;
  if (floor) {
    fetchRooms(floor);
  } else {
    rooms.value = [];
  }
});

const checkIn = async () => {
  try {
    // PATCH /students/{id}/checked-in with { checkedIn: true }
    await axios.patch(`/students/${selectedStudent.value}/checked-in`, { checkedIn: true });
    showCheckInPopup.value = false;
    fetchStudents();
    // Reset fields after check-in
    selectedFloor.value = '';
    selectedRoom.value = '';
    selectedStudent.value = null;
  } catch (error) {
    console.error('Error checking in:', error);
  }
};

const checkOut = async () => {
  try {
    await axios.patch(`/students/${selectedStudent.value}/checked-in`, { checkedIn: false });
    showCheckOutPopup.value = false;
    fetchStudents();
    // Reset fields after check-out
    selectedFloor.value = '';
    selectedRoom.value = '';
    selectedStudent.value = null;
  } catch (error) {
    console.error('Error checking out:', error);
  }
};

const openCheckInPopup = () => {
  showCheckInPopup.value = true;
};

const openCheckOutPopup = () => {
  showCheckOutPopup.value = true;
};

const closePopups = () => {
  showCheckInPopup.value = false;
  showCheckOutPopup.value = false;
  selectedFloor.value = '';
  selectedRoom.value = '';
  selectedStudent.value = null;
};

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value;
  localStorage.setItem('isDarkMode', isDarkMode.value);
  if (isDarkMode.value) {
    document.body.classList.add('dark-mode');
  } else {
    document.body.classList.remove('dark-mode');
  }
};

const filteredStudents = computed(() => {
  if (!selectedRoom.value) return [];
  return students.value.filter(student => String(student.room) === String(selectedRoom.value));
});
</script>

<template>
  <div>
    <!-- Navbar -->
    <nav class="navbar">
      <div class="navbar-left">
        <h1>Dienesta viesnīcas ieraksti</h1>
      </div>
      <div class="navbar-right"></div>
    </nav>

    <!-- Content -->
    <div class="content">
      <!-- Check-In/Check-Out Card -->
      <div class="container">
        <h2>Check-In/Check-Out</h2>
        <div class="button-group">
          <button class="check-in" @click="openCheckInPopup">Check In</button>
          <button class="check-out" @click="openCheckOutPopup">Check Out</button>
        </div>
      </div>
    </div>

    <!-- Check-In Popup -->
    <div v-if="showCheckInPopup" class="popup" @click.self="closePopups">
      <div class="popup-content">
        <h2>Check In</h2>
        <!-- Step 1: Select Floor -->
        <h3>Select Floor</h3>
        <select v-model="selectedFloor">
          <option value="" disabled>Select Floor</option>
          <option v-for="floor in floors" :key="floor" :value="floor">{{ floor }}</option>
        </select>
        <!-- Step 2: Select Room (only after floor is selected) -->
        <h3 v-if="selectedFloor">Select Room</h3>
        <select v-if="selectedFloor" v-model="selectedRoom">
          <option value="" disabled>Select Room</option>
          <option v-for="room in rooms" :key="room.id" :value="room.room">
            Room {{ room.room }}
          </option>
        </select>
        <!-- Step 3: Select Student (only after room is selected) -->
        <h3 v-if="selectedRoom">Select Student</h3>
        <select v-if="selectedRoom" v-model="selectedStudent">
          <option value="" disabled>Select Student</option>
          <option v-for="student in filteredStudents" :key="student.id" :value="student.id">
            {{ student.name }} {{ student.surname }}
          </option>
        </select>
        <div class="popup-actions">
          <button class="check-in" @click="checkIn" :disabled="!selectedStudent">Check In</button>
          <button class="close" @click="closePopups">Close</button>
        </div>
      </div>
    </div>

    <!-- Check-Out Popup -->
    <div v-if="showCheckOutPopup" class="popup" @click.self="closePopups">
      <div class="popup-content">
        <h2>Check Out</h2>
        <!-- Step 1: Select Floor -->
        <h3>Select Floor</h3>
        <select v-model="selectedFloor">
          <option value="" disabled>Select Floor</option>
          <option v-for="floor in floors" :key="floor" :value="floor">{{ floor }}</option>
        </select>
        <!-- Step 2: Select Room (only after floor is selected) -->
        <h3 v-if="selectedFloor">Select Room</h3>
        <select v-if="selectedFloor" v-model="selectedRoom">
          <option value="" disabled>Select Room</option>
          <option v-for="room in rooms" :key="room.id" :value="room.room">
            Room {{ room.room }}
          </option>
        </select>
        <!-- Step 3: Select Student (only after room is selected) -->
        <h3 v-if="selectedRoom">Select Student</h3>
        <select v-if="selectedRoom" v-model="selectedStudent">
          <option value="" disabled>Select Student</option>
          <option v-for="student in filteredStudents" :key="student.id" :value="student.id">
            {{ student.name }} {{ student.surname }}
          </option>
        </select>
        <div class="popup-actions">
          <button class="check-out" @click="checkOut" :disabled="!selectedStudent">Check Out</button>
          <button class="close" @click="closePopups">Close</button>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
      <div class="footer-left">
        <button @click="toggleDarkMode" class="theme-toggle">
          <Sun v-if="isDarkMode" class="text-white w-6 h-6" />
          <Moon v-else class="text-white w-6 h-6" />
        </button>
      </div>
      <div class="footer-center">
        Contact us at: <a href="mailto:info@example.com" class="login-icon">info@example.com</a>
      </div>
      <div class="footer-right">
        <a href="/login" class="login-icon">
          <svg class="text-white w-6 h-6" viewBox="0 0 24 24" fill="none"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="10 17 15 12 10 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="15" y1="12" x2="3" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.button-group {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 20px;
}

.footer-left {
  display: flex;
  gap: 10px;
  align-items: center;
}

.footer-center {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 1rem;
}

.footer-right {
  display: flex;
  align-items: center;
}

.theme-toggle {
  background: none;
  border: none;
  cursor: pointer;
  padding: 5px;
}

.theme-toggle svg {
  width: 24px;
  height: 24px;
  color: var(--footer-text);
}

.login-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  color: var(--footer-text);
  gap: 6px;
  font-size: 1rem;
}

.login-icon svg {
  width: 24px;
  height: 24px;
  color: var(--footer-text);
}
</style>