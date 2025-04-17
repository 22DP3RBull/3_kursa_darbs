<template>
  <div class="admin-dashboard">
    <header class="header">
      <h1>Dienesta viesnīcas ieraksti</h1>
      <button class="add-student-icon-button" @click="openAddStudentPopup">
        <img src="https://img.icons8.com/ios-filled/50/ffffff/plus-math.png" alt="Add Student Icon" />
      </button>
    </header>

    <div class="content">
      <!-- Add Student Button Below Header -->
      <div class="add-student-container">
        <button class="add-student-button" @click="openAddStudentPopup">
          <img src="https://img.icons8.com/ios-filled/24/ffffff/plus-math.png" alt="Add Icon" />
          Add Student
        </button>
      </div>

      <!-- Filter Icon sticking out of the left side -->
      <div :class="['filter-toggle', { 'slide-out': showFilter }]" @click="toggleFilter">
        <img src="https://img.icons8.com/ios-filled/50/ffffff/filter.png" alt="Filter Icon" />
      </div>

      <!-- Filter Card -->
      <div :class="['filter-card', { 'slide-out': showFilter }]">
        <h2>Filter Students</h2>
        <input v-model="searchQuery" placeholder="Search by name, surname, or room number" @input="applyFilters" />
        <div>
          <label>Floor:</label>
          <div class="floor-buttons">
            <button v-for="floor in floors" :key="floor" @click="filterByFloor(floor)" :class="{ active: selectedFloor === floor }">
              {{ floor }}
            </button>
          </div>
        </div>
        <div>
          <label>Checked In Status:</label>
          <div class="status-buttons">
            <button @click="filterByCheckedIn(true)" :class="{ active: checkedInFilter === true }">Checked In</button>
            <button @click="filterByCheckedIn(false)" :class="{ active: checkedInFilter === false }">Not Checked In</button>
          </div>
        </div>
        <button @click="resetFilters" class="reset-filters-button">Reset Filters</button>
      </div>

      <!-- Students Table -->
      <div :class="{ 'table-adjusted': showFilter }">
        <table class="students-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Surname</th>
              <th>Floor</th>
              <th>Room</th>
              <th>Phone Number</th>
              <th>Checked In</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="student in students" :key="student.id">
              <td>{{ student.name }}</td>
              <td>{{ student.surname }}</td>
              <td>{{ student.floor }}</td>
              <td>{{ student.room }}</td>
              <td>{{ student.phone }}</td>
              <td>
                <span :class="['status-circle', student.checkedIn ? 'green' : 'red']"></span>
              </td>
              <td>
                <button @click="openEditStudentPopup(student)" class="edit-button">Edit</button>
                <button @click="openDeletePopup(student)" class="delete-button">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <footer class="footer">
      <p>Contact us at: info@example.com</p>
      <button class="icon-button" @click="logout">
        <img src="https://img.icons8.com/ios-filled/50/ffffff/logout-rounded-left.png" alt="Logout Icon" />
      </button>
    </footer>

    <!-- Add Student Popup -->
    <div v-if="showAddStudentPopup" class="popup-overlay">
      <div class="popup-card">
        <h2>Add Student</h2>
        <form @submit.prevent="addStudent">
          <div>
            <label>Name:</label>
            <input v-model="newStudent.name" @input="validateName('add')" required />
            <small v-if="errors.name" class="error">{{ errors.name }}</small>
          </div>
          <div>
            <label>Surname:</label>
            <input v-model="newStudent.surname" @input="validateSurname('add')" required />
            <small v-if="errors.surname" class="error">{{ errors.surname }}</small>
          </div>
          <div>
            <label>Floor:</label>
            <select v-model="newStudent.floor" @change="validateFloor('add')" required>
              <option value="" disabled>Select Floor</option>
              <option v-for="floor in floors" :key="floor" :value="floor">{{ floor }}</option>
            </select>
            <small v-if="errors.floor" class="error">{{ errors.floor }}</small>
          </div>
          <div>
            <label>Room:</label>
            <input v-model="newStudent.room" @input="validateRoom('add')" required />
            <small v-if="errors.room" class="error">{{ errors.room }}</small>
          </div>
          <div>
            <label>Phone:</label>
            <input v-model="newStudent.phone" @input="validatePhone('add')" required />
            <small v-if="errors.phone" class="error">{{ errors.phone }}</small>
          </div>
          <div>
            <label>Email:</label>
            <input v-model="newStudent.email" @input="validateEmail('add')" required />
            <small v-if="errors.email" class="error">{{ errors.email }}</small>
          </div>
          <button type="submit" :disabled="hasErrors">Submit</button>
          <small v-if="hasErrors" class="error-large">Please fix the errors above before submitting.</small>
          <button type="button" @click="closeAddStudentPopup">Cancel</button>
        </form>
      </div>
    </div>

    <!-- Edit Student Popup -->
    <div v-if="showEditStudentPopup" class="popup-overlay">
      <div class="popup-card">
        <h2>Edit Student</h2>
        <form @submit.prevent="updateStudent">
          <div>
            <label>Name:</label>
            <input v-model="editStudentData.name" @input="validateName('edit')" required />
            <small v-if="errors.name" class="error">{{ errors.name }}</small>
          </div>
          <div>
            <label>Surname:</label>
            <input v-model="editStudentData.surname" @input="validateSurname('edit')" required />
            <small v-if="errors.surname" class="error">{{ errors.surname }}</small>
          </div>
          <div>
            <label>Floor:</label>
            <select v-model="editStudentData.floor" @change="validateFloor('edit')" required>
              <option value="" disabled>Select Floor</option>
              <option v-for="floor in floors" :key="floor" :value="floor">{{ floor }}</option>
            </select>
            <small v-if="errors.floor" class="error">{{ errors.floor }}</small>
          </div>
          <div>
            <label>Room:</label>
            <input v-model="editStudentData.room" @input="validateRoom('edit')" required />
            <small v-if="errors.room" class="error">{{ errors.room }}</small>
          </div>
          <div>
            <label>Phone:</label>
            <input v-model="editStudentData.phone" @input="validatePhone('edit')" required />
            <small v-if="errors.phone" class="error">{{ errors.phone }}</small>
          </div>
          <div>
            <label>Email:</label>
            <input v-model="editStudentData.email" @input="validateEmail('edit')" required />
            <small v-if="errors.email" class="error">{{ errors.email }}</small>
          </div>
          <button type="submit" :disabled="hasErrors">Update</button>
          <small v-if="hasErrors" class="error-large">Please fix the errors above before updating.</small>
          <button type="button" @click="closeEditStudentPopup">Cancel</button>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Popup -->
    <div v-if="showDeletePopup" class="popup-overlay">
      <div class="popup-card">
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete <strong>{{ studentToDelete.name }} {{ studentToDelete.surname }}</strong> (Room: {{ studentToDelete.room }})?</p>
        <div class="popup-actions">
          <button @click="confirmDeleteStudent" class="confirm-button">Confirm</button>
          <button @click="closeDeletePopup" class="cancel-button">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Success Notification -->
    <div v-if="showSuccessNotification" class="success-notification">
      {{ successMessage }}
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      showFilter: false,
      showAddStudentPopup: false,
      showEditStudentPopup: false,
      showDeletePopup: false,
      showSuccessNotification: false,
      successMessage: '',
      searchQuery: '',
      selectedFloor: '',
      checkedInFilter: null, // null = No filter, true = Checked In, false = Not Checked In
      students: [],
      floors: [1, 2, 3, 4, 5],
      newStudent: {
        name: '',
        surname: '',
        room: '',
        floor: '',
        phone: '',
        email: '',
        checkedIn: false,
      },
      editStudentData: {
        id: null,
        name: '',
        surname: '',
        room: '',
        floor: '',
        phone: '',
        email: '',
      },
      studentToDelete: {}, // Holds the student data for deletion confirmation
      roomOptions: [],
      errors: {},
    };
  },
  computed: {
    hasErrors() {
      return Object.values(this.errors).some((error) => error); // Check if any error exists
    },
  },
  methods: {
    toggleFilter() {
      this.showFilter = !this.showFilter;
    },
    openAddStudentPopup() {
      this.showAddStudentPopup = true;
    },
    closeAddStudentPopup() {
      this.showAddStudentPopup = false;
      this.newStudent = {
        name: '',
        surname: '',
        room: '',
        floor: '',
        phone: '',
        email: '',
        checkedIn: false,
      };
      this.errors = {};
    },
    openEditStudentPopup(student) {
      this.editStudentData = { ...student };
      this.showEditStudentPopup = true;
    },
    closeEditStudentPopup() {
      this.showEditStudentPopup = false;
      this.editStudentData = {
        id: null,
        name: '',
        surname: '',
        room: '',
        floor: '',
        phone: '',
        email: '',
      };
      this.errors = [];
    },
    openDeletePopup(student) {
      this.studentToDelete = student;
      this.showDeletePopup = true;
    },
    closeDeletePopup() {
      this.showDeletePopup = false;
      this.studentToDelete = {};
    },
    validateName(context) {
      const name = context === 'add' ? this.newStudent.name : this.editStudentData.name;
      const regex = /^[a-zA-Z\s]+$/;
      this.errors.name = !regex.test(name) ? 'Name must contain only letters and spaces.' : '';
    },
    validateSurname(context) {
      const surname = context === 'add' ? this.newStudent.surname : this.editStudentData.surname;
      const regex = /^[a-zA-Z\s]+$/;
      this.errors.surname = !regex.test(surname) ? 'Surname must contain only letters and spaces.' : '';
    },
    validateFloor(context) {
      const floor = context === 'add' ? this.newStudent.floor : this.editStudentData.floor;
      this.errors.floor = floor < 1 || floor > 5 ? 'Floor must be between 1 and 5.' : '';

      // Trigger room validation when the floor changes
      this.validateRoom(context);
    },
    validateRoom(context) {
      const floor = context === 'add' ? this.newStudent.floor : this.editStudentData.floor;
      const room = context === 'add' ? this.newStudent.room : this.editStudentData.room;

      if (!floor || !room) {
        this.errors.room = 'Room and floor must be specified.';
        return;
      }

      // Ensure the room number is valid for the selected floor
      const minRoom = floor * 100 + 1; // e.g., 401 for 4th floor
      const maxRoom = floor * 100 + 28; // e.g., 428 for 4th floor
      const isValidRoom = room >= minRoom && room <= maxRoom;

      this.errors.room = !isValidRoom
        ? `Room must be between ${minRoom} and ${maxRoom} for floor ${floor}.`
        : '';
    },
    validatePhone(context) {
      const phone = context === 'add' ? this.newStudent.phone : this.editStudentData.phone;
      const regex = /^\d{8}$/;
      this.errors.phone = !regex.test(phone) ? 'Phone number must be exactly 8 digits.' : '';
    },
    validateEmail(context) {
      const email = context === 'add' ? this.newStudent.email : this.editStudentData.email;
      const currentEmail = context === 'edit' ? this.editStudentData.email : null;
      const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!regex.test(email)) {
        this.errors.email = 'Email must be valid and contain no spaces.';
      } else if (email !== currentEmail) {
        const isEmailTaken = this.students.some(
          (student) => student.email === email && student.id !== this.editStudentData.id
        );
        this.errors.email = isEmailTaken ? 'This email is already taken by another student.' : '';
      } else {
        this.errors.email = '';
      }
    },
    async addStudent() {
      if (this.hasErrors) return; // Prevent submission if there are errors

      try {
        const payload = {
          name: this.newStudent.name,
          surname: this.newStudent.surname,
          room: this.newStudent.room,
          floor: this.newStudent.floor,
          phone: this.newStudent.phone,
          email: this.newStudent.email,
          checkedIn: this.newStudent.checkedIn || false,
        };

        const response = await axios.post('/students', payload);
        this.students.push(response.data);
        this.closeAddStudentPopup();
        this.showSuccessNotification = true;
        this.successMessage = 'Student added successfully!';
        setTimeout(() => {
          this.showSuccessNotification = false;
        }, 3000);
      } catch (error) {
        console.error('Failed to add student:', error);
        if (error.response?.data?.errors) {
          this.errors = { ...this.errors, ...error.response.data.errors };
        }
      }
    },
    async updateStudent() {
      if (this.hasErrors) return; // Prevent update if there are errors

      try {
        const payload = {
          name: this.editStudentData.name,
          surname: this.editStudentData.surname,
          room: this.editStudentData.room,
          floor: this.editStudentData.floor,
          phone: this.editStudentData.phone,
          email: this.editStudentData.email,
          checkedIn: this.editStudentData.checkedIn || false, // Ensure checkedIn is included
        };

        const response = await axios.put(`/students/${this.editStudentData.id}`, payload);
        const index = this.students.findIndex((s) => s.id === this.editStudentData.id);
        if (index !== -1) this.students.splice(index, 1, response.data);

        this.closeEditStudentPopup();
        this.showSuccessNotification = true;
        this.successMessage = 'Student updated successfully!';
        setTimeout(() => {
          this.showSuccessNotification = false;
        }, 3000);
      } catch (error) {
        console.error('Failed to update student:', error);
        if (error.response?.data?.errors) {
          this.errors = { ...this.errors, ...error.response.data.errors };
        }
      }
    },
    async confirmDeleteStudent() {
      try {
        await axios.delete(`/students/${this.studentToDelete.id}`);
        this.students = this.students.filter((student) => student.id !== this.studentToDelete.id);
        this.closeDeletePopup();
        this.showSuccessNotification = true;
        this.successMessage = 'Student deleted successfully!';
        setTimeout(() => {
          this.showSuccessNotification = false;
        }, 3000);
      } catch (error) {
        console.error('Error deleting student:', error);
      }
    },
    async deleteStudent(id) {
      if (!confirm('Are you sure you want to delete this student?')) return;
      try {
        await axios.delete(`/students/${id}`);
        this.students = this.students.filter((student) => student.id !== id);
        this.showSuccessNotification = true;
        this.successMessage = 'Student deleted successfully!';
        setTimeout(() => {
          this.showSuccessNotification = false;
        }, 3000);
      } catch (error) {
        console.error('Error deleting student:', error);
      }
    },
    async applyFilters() {
      console.log('applyFilters called');
      console.log('Filter parameters:', {
        search: this.searchQuery,
        floor: this.selectedFloor,
        checkedIn: this.checkedInFilter,
      });

      try {
        const response = await axios.get('/students', {
          params: {
            search: this.searchQuery,
            floor: this.selectedFloor,
            checkedIn: this.checkedInFilter,
          },
        });
        this.students = response.data;
        console.log('Filtered students:', this.students);
      } catch (error) {
        console.error('Error applying filters:', error);
      }
    },
    filterByFloor(floor) {
      this.selectedFloor = this.selectedFloor === floor ? '' : floor;
      this.applyFilters();
    },
    filterByCheckedIn(status) {
      this.checkedInFilter = status;
      this.applyFilters();
    },
    resetFilters() {
      this.searchQuery = '';
      this.selectedFloor = '';
      this.checkedInFilter = null; // Reset to no filter
      this.applyFilters();
    },
    async fetchStudents() {
      try {
        const response = await axios.get('/students');
        this.students = response.data;
      } catch (error) {
        console.error('Error fetching students:', error);
      }
    },
    logout() {
      axios.post('/logout').then(() => {
        window.location.href = '/';
      });
    },
    reapplyStyles() {
      // Force reapplication of styles
      const content = document.querySelector('.content');
      if (content) {
        content.style.display = 'none';
        setTimeout(() => {
          content.style.display = '';
        }, 0);
      }
    },
  },
  mounted() {
    this.fetchStudents();
    this.reapplyStyles(); // Reapply styles after the component is mounted
  },
};
</script>

<style scoped src="../../css/admindashboard.css"></style>

<style scoped>
.success-notification {
  position: fixed;
  bottom: 80px; /* Adjusted to appear above the footer */
  left: 50%;
  transform: translateX(-50%);
  background-color: #4caf50;
  color: white;
  padding: 10px 20px;
  border-radius: 5px;
  font-size: 16px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  animation: fade-in-out 3s ease-in-out;
}

@keyframes fade-in-out {
  0% {
    opacity: 0;
    transform: translateX(-50%) translateY(20px);
  }
  10% {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
  90% {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
  100% {
    opacity: 0;
    transform: translateX(-50%) translateY(20px);
  }
}

.error {
  color: red;
  font-size: 12px;
  margin-top: 5px;
}

.error-large {
  color: red;
  font-size: 14px;
  margin-top: 10px;
  display: block;
  text-align: center;
}

.popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.popup-card {
  background: #707070; /* Updated background color for better visibility */
  color: white; /* Ensure text is readable on the darker background */
  padding: 20px;
  border-radius: 8px;
  width: 400px;
  text-align: center;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

.popup-card h2 {
  margin-top: 0;
  font-size: 1.5rem;
  color: white; /* Ensure the heading is readable */
}

.popup-card p {
  margin: 15px 0;
  font-size: 1rem;
  color: white; /* Ensure the paragraph text is readable */
}

.popup-actions {
  display: flex;
  justify-content: space-around;
  margin-top: 20px;
}

.confirm-button {
  background-color: #e53935;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
}

.cancel-button {
  background-color: #505050;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
}

.confirm-button:hover {
  background-color: #d32f2f;
}

.cancel-button:hover {
  background-color: #404040;
}
</style>
