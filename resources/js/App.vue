<template>
    <div class="container">
        <h1 class="text-center my-4">
            Meeting Room Booking App
        </h1>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="date" class="form-label">Select Date</label>
                    <input type="date" id="date" class="form-control" v-model="selectedDate">
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="userName" class="form-label">User Name:</label>
                    <input type="text" id="userName" class="form-control" v-model="userName" placeholder="Enter your name">
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="time" class="form-label">Select Time:</label>
                    <div class="d-flex align-items-center">
                        <select id="time" class="form-select me-2" v-model="selectedTime" @change="getAvailableRooms">
                            <option v-for="hour in hours" :key="hour" :value="hour">{{ hour }}:00</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-6" v-if="availableRooms.length > 0">
                <div class="mb-3">
                    <label for="select" class="form-label">Available Rooms:</label>
                    <div class="d-flex align-items-center">
                        <select id="select" class="form-select me-2" v-model="selectedRoom" tabindex="0" aria-label="Select an available room">
                            <option v-for="room in listAvailableRooms" :key="room" :value="room">{{ room }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-12 text-center">
                <button class="btn btn-primary btn-sm" @click="makeBooking" tabindex="0" aria-label="Make booking">Make booking</button>
            </div>
            <div class="col-md-12">
                <div v-if="error" class="alert alert-danger mt-2">
                    {{ error }}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Room ID</th>
                            <th>User Name</th>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in bookings" :key="item.id">
                            <td>{{ item.room_id }}</td>
                            <td>{{ item.user_name }}</td>
                            <td>{{ item.date }}</td>
                            <td>{{ item.start_time }}</td>
                            <td>{{ item.end_time }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        const today = new Date();
        const currentHour = today.getHours();
        return {
            selectedDate: today.toISOString().substr(0, 10),
            selectedTime: currentHour,
            availableRooms: [],
            error: null,
            hours: Array.from({ length: 24 }, (_, i) => i)
        };
    },
    mounted() {
        this.refresh();
    },
    methods: {
        async getAvailableRooms() {
            this.error = null;
            this.availableRooms = [];
            this.listAvailableRooms = [];
            try {
                const formattedTime = this.selectedTime.toString().padStart(2, '0');
                const response = await fetch(`api/available-rooms?date=${this.selectedDate}&time=${formattedTime}:00`);
                if (!response.ok) {
                    throw new Error('Failed to fetch available rooms');
                }
                this.availableRooms = await response.json();
                for (const availableRoom of this.availableRooms) {
                    this.listAvailableRooms.push(`#${availableRoom.id} ${availableRoom.name}`)
                }
            } catch (err) {
                this.error = err.message;
            }
        },
        async getBookings() {
            this.error = null;
            this.bookings = [];
            try {
                const response = await fetch(`api/bookings`);
                if (!response.ok) {
                    throw new Error('Failed to get bookings');
                }
                this.bookings = await response.json();
            } catch (err) {
                this.error = err.message;
            }
        },
        async makeBooking() {
            this.error = null;
            try {
                // Get user_name from input
                if (!this.userName) {
                    document.getElementById('userName').focus();
                    throw new Error('User name is required');
                }

                // Get the selected room id from the availableRooms array
                const selectedRoom = this.availableRooms.find(room => `#${room.id} ${room.name}` === this.selectedRoom);
                if (!selectedRoom) {
                    document.getElementById('select').focus();
                    throw new Error('Selected room not found');
                }

                // Build start_time and end_time
                const startTime = `${this.selectedDate}T${this.selectedTime.toString().padStart(2, '0')}:00:00Z`;
                const endTime = new Date(new Date(startTime).getTime() + 60 * 60 * 1000).toISOString().replace('.000Z', 'Z');

                // Make a post request to /api/bookings
                const response = await fetch('/api/bookings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        room_id: selectedRoom.id,
                        user_name: this.userName,
                        date: this.selectedDate,
                        start_time: startTime,
                        end_time: endTime
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    return this.error = errorData.error || 'Failed to make booking';
                }

                // Refresh the bookings and available rooms
                this.refresh();
            } catch (err) {
                this.error = err.message;
            }
        },
        async refresh() {
            this.getBookings();
            this.getAvailableRooms();
        }
    }
};
</script>