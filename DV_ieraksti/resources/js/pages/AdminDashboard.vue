<template>
  <div>
    <header class="header">
      <h1>Administrācijas pārskats</h1>
      <button class="logout-button" @click="logout">Logout</button>
    </header>

    <div class="container">
      <button class="filter-button" @click="toggleFilterCard">
        <img src="https://img.icons8.com/ios-filled/50/000000/filter.png" alt="Filter Icon" />
      </button>

      <div :class="['filter-card', { open: showFilterCard }]">
        <h2>Filtri</h2>
        <input v-model="searchQuery" placeholder="Meklēt pēc vārda, uzvārda vai istabas" />
        <button class="reset" @click="resetFilters">Atiestatīt filtrus</button>
        <button class="add" @click="openAddStudentModal">Pievienot studentu</button>
        <div class="floor-filters">
          <button class="floor" v-for="floor in floors" :key="floor" @click="filterByFloor(floor)">
            {{ floor }}. stāvs
          </button>
        </div>
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Vārds</th>
              <th>Uzvārds</th>
              <th>Istaba</th>
              <th>Telefons</th>
              <th>E-pasts</th>
              <th>Reģistrācijas statuss</th>
              <th>Darbības</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="student in paginatedStudents" :key="student.id">
              <td>{{ student.name }}</td>
              <td>{{ student.surname }}</td>
              <td>{{ student.room }}</td>
              <td>{{ student.phone }}</td>
              <td>{{ student.email }}</td>
              <td>
                <span v-if="student.check_in_status" class="text-green-500">✔️</span>
                <span v-else class="text-red-500">❌</span>
              </td>
              <td>
                <button class="edit" @click="openEditModal(student)">Rediģēt</button>
                <button class="delete" @click="confirmDelete(student.id)">Dzēst</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <button @click="prevPage" :disabled="currentPage === 1">Iepriekšējā</button>
        <span>Page {{ currentPage }} of {{ totalPages }}</span>
        <button @click="nextPage" :disabled="currentPage === totalPages">Nākamā</button>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="modal">
      <div class="modal-content">
        <h2>Rediģēt studentu</h2>
        <form @submit.prevent="updateStudent">
          <div>
            <label for="editName">Vārds</label>
            <input id="editName" v-model="editForm.name" type="text" required />
          </div>
          <div>
            <label for="editSurname">Uzvārds</label>
            <input id="editSurname" v-model="editForm.surname" type="text" required />
          </div>
          <div>
            <label for="editRoom">Istaba</label>
            <input id="editRoom" v-model="editForm.room" type="number" required />
          </div>
          <div>
            <label for="editPhone">Telefons</label>
            <input id="editPhone" v-model="editForm.phone" type="text" required />
          </div>
          <div>
            <label for="editEmail">E-pasts</label>
            <input id="editEmail" v-model="editForm.email" type="email" required />
          </div>
          <div>
            <label for="editCheckInStatus">Reģistrācijas statuss</label>
            <input id="editCheckInStatus" v-model="editForm.check_in_status" type="checkbox" />
          </div>
          <button type="submit">Saglabāt</button>
          <button type="button" @click="closeEditModal">Atcelt</button>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div v-if="showDeleteConfirm" class="modal">
      <div class="modal-content">
        <h2>Apstiprināt dzēšanu</h2>
        <p>Vai tiešām vēlaties dzēst šo studentu?</p>
        <button @click="deleteStudent">Jā</button>
        <button @click="closeDeleteConfirm">Nē</button>
      </div>
    </div>

    <!-- Add Student Modal -->
    <div v-if="showAddStudentModal" class="modal">
      <div class="modal-content">
        <h2>Pievienot studentu</h2>
        <form @submit.prevent="addStudent">
          <div>
            <label for="addName">Vārds</label>
            <input id="addName" v-model="addForm.name" type="text" required />
          </div>
          <div>
            <label for="addSurname">Uzvārds</label>
            <input id="addSurname" v-model="addForm.surname" type="text" required />
          </div>
          <div>
            <label for="addRoom">Istaba</label>
            <input id="addRoom" v-model="addForm.room" type="number" required />
          </div>
          <div>
            <label for="addPhone">Telefons</label>
            <input id="addPhone" v-model="addForm.phone" type="text" required />
          </div>
          <div>
            <label for="addEmail">E-pasts</label>
            <input id="addEmail" v-model="addForm.email" type="email" required />
          </div>
          <div>
            <label for="addCheckInStatus">Reģistrācijas statuss</label>
            <input id="addCheckInStatus" v-model="addForm.check_in_status" type="checkbox" />
          </div>
          <button type="submit">Pievienot</button>
          <button type="button" @click="closeAddStudentModal">Atcelt</button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import '../../css/admindashboard.css';

const students = ref([]);
const searchQuery = ref('');
const floors = [1, 2, 3, 4, 5];
const currentPage = ref(1);
const itemsPerPage = 10;

const showFilterCard = ref(false);
const showEditModal = ref(false);
const showDeleteConfirm = ref(false);
const showAddStudentModal = ref(false);

const editForm = ref({
  id: null,
  name: '',
  surname: '',
  room: '',
  phone: '',
  email: '',
  check_in_status: false,
});

const addForm = ref({
  name: '',
  surname: '',
  room: '',
  phone: '',
  email: '',
  check_in_status: false,
});

const deleteId = ref(null);

const fetchStudents = async () => {
  const response = await axios.get('/students');
  students.value = response.data;
};

const filteredStudents = computed(() => {
  return students.value.filter(student => {
    return (
      student.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      student.surname.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      student.room.toString().includes(searchQuery.value)
    );
  });
});

const paginatedStudents = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredStudents.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(filteredStudents.value.length / itemsPerPage);
});

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

const toggleFilterCard = () => {
  showFilterCard.value = !showFilterCard.value;
};

const resetFilters = () => {
  searchQuery.value = '';
  fetchStudents();
};

const filterByFloor = (floor) => {
  searchQuery.value = '';
  students.value = students.value.filter(student => student.floor === floor);
};

const openEditModal = (student) => {
  editForm.value = { ...student };
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
};

const updateStudent = async () => {
  await axios.patch(`/students/${editForm.value.id}`, editForm.value);
  fetchStudents();
  closeEditModal();
};

const confirmDelete = (id) => {
  deleteId.value = id;
  showDeleteConfirm.value = true;
};

const closeDeleteConfirm = () => {
  showDeleteConfirm.value = false;
};

const deleteStudent = async () => {
  await axios.delete(`/students/${deleteId.value}`);
  fetchStudents();
  closeDeleteConfirm();
};

const openAddStudentModal = () => {
  showAddStudentModal.value = true;
};

const closeAddStudentModal = () => {
  showAddStudentModal.value = false;
};

const addStudent = async () => {
  await axios.post('/students', addForm.value);
  fetchStudents();
  closeAddStudentModal();
};

const logout = async () => {
  await axios.post('/logout');
  window.location.href = '/';
};

onMounted(() => {
  fetchStudents();
});

onUnmounted(() => {
  // Cleanup if necessary
});
</script>

<style>
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-content {
  background: white;
  padding: 20px;
  border-radius: 5px;
}
</style>
