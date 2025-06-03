<script setup lang="js">
import { ref, computed } from 'vue';
import axios from 'axios';
import { Sun, Moon, LogIn } from 'lucide-vue-next';
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

const fetchRooms = async () => {
  try {
    const response = await axios.get('/rooms'); // Ensure this endpoint exists
    console.log('Rooms fetched:', response.data); // Log the response for debugging
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

const checkIn = async () => {
  try {
    await axios.post('/check-in', { student_id: selectedStudent.value });
    showCheckInPopup.value = false;
    fetchStudents();
  } catch (error) {
    console.error('Error checking in:', error);
  }
};

const checkOut = async () => {
  try {
    await axios.post('/check-out', { student_id: selectedStudent.value });
    showCheckOutPopup.value = false;
    fetchStudents();
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
    document.documentElement.classList.add('dark'); // Add 'dark' class for dark mode
  } else {
    document.documentElement.classList.remove('dark'); // Remove 'dark' class for light mode
  }
};

const filteredRooms = computed(() => {
  if (!selectedFloor.value) return [];
  return rooms.value.filter(room => room.floor == selectedFloor.value); // Ensure '==' for type coercion
});

const filteredStudents = computed(() => {
  if (!selectedRoom.value) return [];
  return students.value.filter(student => student.room === selectedRoom.value);
});

fetchRooms();
fetchStudents();
</script>

<template>
  <div :class="{ 'dark-mode': isDarkMode }">
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
        <h3>Select Floor</h3>
        <select v-model="selectedFloor">
          <option value="" disabled>Select Floor</option>
          <option v-for="floor in floors" :key="floor" :value="floor">{{ floor }}</option>
        </select>
        <h3 v-if="selectedFloor">Select Room</h3>
        <select v-if="selectedFloor" v-model="selectedRoom">
          <option value="" disabled>Select Room</option>
          <option v-for="room in filteredRooms" :key="room.id" :value="room.id">
            Room {{ room.room }}
          </option>
        </select>
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
        <h3>Select Floor</h3>
        <select v-model="selectedFloor">
          <option value="" disabled>Select Floor</option>
          <option v-for="floor in floors" :key="floor" :value="floor">{{ floor }}</option>
        </select>
        <h3 v-if="selectedFloor">Select Room</h3>
        <select v-if="selectedFloor" v-model="selectedRoom">
          <option value="" disabled>Select Room</option>
          <option v-for="room in filteredRooms" :key="room.id" :value="room.id">
            Room {{ room.room }}
          </option>
        </select>
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
      <div class="footer-right">
        <a href="/login" class="login-icon">
          <LogIn class="text-white w-6 h-6" />
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
}

.login-icon svg {
  width: 24px;
  height: 24px;
  color: var(--footer-text);
}
</style>