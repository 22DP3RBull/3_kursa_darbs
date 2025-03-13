<template>
  <div>
    <header class="header">
      <h1>Dienesta viesnīcas ieraksti</h1>
    </header>

    <div class="container">
      <button class="check-in" @click="openCheckInPopup">Check In</button>
      <button class="check-out" @click="openCheckOutPopup">Check Out</button>
    </div>

    <footer class="footer">
      <p>Contact us at: info@example.com</p>
      <button class="icon-button" @click="redirectToLogin">
        <img src="https://img.icons8.com/ios-filled/50/ffffff/login-rounded-right.png" alt="Login Icon" />
      </button>
    </footer>

    <!-- Check-In Popup -->
    <div v-if="showCheckInPopup" class="popup">
      <div class="popup-content">
        <h2>Select Floor</h2>
        <select v-model="selectedFloor" @change="fetchStudentsByFloor">
          <option value="" disabled>Select Floor</option>
          <option v-for="floor in floors" :key="floor" :value="floor">{{ floor }}</option>
        </select>
        <div v-if="studentsByFloor.length">
          <h3>Select Student</h3>
          <ul>
            <li v-for="student in studentsByFloor" :key="student.id">
              <button @click="checkIn(student.id)">{{ student.name }} {{ student.surname }} (Room: {{ student.room }})</button>
            </li>
          </ul>
        </div>
        <button @click="closeCheckInPopup">Close</button>
      </div>
    </div>

    <!-- Check-Out Popup -->
    <div v-if="showCheckOutPopup" class="popup">
      <div class="popup-content">
        <h2>Select Floor</h2>
        <select v-model="selectedFloor" @change="fetchStudentsByFloor">
          <option value="" disabled>Select Floor</option>
          <option v-for="floor in floors" :key="floor" :value="floor">{{ floor }}</option>
        </select>
        <div v-if="studentsByFloor.length">
          <h3>Select Student</h3>
          <ul>
            <li v-for="student in studentsByFloor" :key="student.id">
              <button @click="checkOut(student.id)">{{ student.name }} {{ student.surname }} (Room: {{ student.room }})</button>
            </li>
          </ul>
        </div>
        <button @click="closeCheckOutPopup">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import '../../css/mainpage.css';

const students = ref([]);
const showCheckInPopup = ref(false);
const showCheckOutPopup = ref(false);
const selectedFloor = ref('');
const studentsByFloor = ref([]);
const floors = [1, 2, 3, 4, 5];

const fetchStudents = async () => {
  const response = await axios.get('/students');
  students.value = response.data;
};

const fetchStudentsByFloor = () => {
  studentsByFloor.value = students.value.filter(student => student.floor === selectedFloor.value);
};

const checkIn = async (id) => {
  await axios.patch(`/students/${id}`, { check_in_status: true });
  fetchStudents();
  closeCheckInPopup();
};

const checkOut = async (id) => {
  await axios.patch(`/students/${id}`, { check_in_status: false });
  fetchStudents();
  closeCheckOutPopup();
};

const openCheckInPopup = () => {
  showCheckInPopup.value = true;
};

const closeCheckInPopup = () => {
  showCheckInPopup.value = false;
  selectedFloor.value = '';
  studentsByFloor.value = [];
};

const openCheckOutPopup = () => {
  showCheckOutPopup.value = true;
};

const closeCheckOutPopup = () => {
  showCheckOutPopup.value = false;
  selectedFloor.value = '';
  studentsByFloor.value = [];
};

const redirectToLogin = () => {
  window.location.href = '/login';
};

onMounted(fetchStudents);
</script>
