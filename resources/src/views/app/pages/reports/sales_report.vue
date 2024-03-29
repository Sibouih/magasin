<template>
  <div class="main-content">
    <breadcumb :page="$t('SalesReport')" :folder="$t('Reports')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
      <b-col md="12" class="text-center">
        <date-range-picker
          ref="picker"
          :opens="opens"
          :locale-data="{ firstDay: 1, format: 'YYYY-MM-DD HH:mm:ss' }"
          :minDate="minDate"
          :maxDate="maxDate"
          :singleDatePicker="singleDatePicker"
          :timePicker="timePicker"
          :timePicker24Hour="timePicker24Hour"
          :showWeekNumbers="showWeekNumbers"
          :showDropdowns="showDropdowns"
          :autoApply="autoApply"
          v-model="dateRange"
          @update="Submit_filter_dateRange"
        >
          <template v-slot:input="picker" style="min-width: 350px;">
            {{ formatDateTime(picker.startDate) }} - {{ formatDateTime(picker.endDate) }}
          </template>
        </date-range-picker>
      </b-col>


    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="rows"
        :group-options="{
          enabled: true,
          headerPosition: 'bottom',
        }"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
        :search-options="{
        placeholder: $t('Search_this_table'),
        enabled: true,
      }"
        :pagination-options="{
        enabled: true,
        mode: 'records',
        nextLabel: 'next',
        prevLabel: 'prev',
      }"
        :styleClass="'mt-5 order-table vgt-table'"
      >
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button variant="outline-info ripple m-1" size="sm" v-b-toggle.sidebar-right>
            <i class="i-Filter-2"></i>
            {{ $t("Filter") }}
          </b-button>
          <b-button @click="Sales_PDF()" size="sm" variant="outline-success ripple m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
           <vue-excel-xlsx
              class="btn btn-sm btn-outline-danger ripple m-1"
              :data="sales"
              :columns="columns"
              :file-name="'sales_report'"
              :file-type="'xlsx'"
              :sheet-name="'sales_report'"
              >
              <i class="i-File-Excel"></i> EXCEL
          </vue-excel-xlsx>
        </div>

        <template slot="table-row" slot-scope="props">
          <div v-if="props.column.field == 'statut'">
            <span
              v-if="props.row.statut == 'completed'"
              class="badge badge-outline-success"
            >{{$t('complete')}}</span>
            <span
              v-else-if="props.row.statut == 'pending'"
              class="badge badge-outline-info"
            >{{$t('Pending')}}</span>
            <span v-else class="badge badge-outline-warning">{{$t('Ordered')}}</span>
          </div>

          <div v-else-if="props.column.field == 'payment_status'">
            <span
              v-if="props.row.payment_status == 'paid'"
              class="badge badge-outline-success"
            >{{$t('Paid')}}</span>
            <span
              v-else-if="props.row.payment_status == 'partial'"
              class="badge badge-outline-primary"
            >{{$t('partial')}}</span>
            <span v-else class="badge badge-outline-warning">{{$t('Unpaid')}}</span>
          </div>
        </template>
      </vue-good-table>
    </b-card>

    <!-- Sidebar Filter -->
    <b-sidebar id="sidebar-right" :title="$t('Filter')" bg-variant="white" right shadow>
      <div class="px-3 py-2">
        <b-row>
          <!-- Reference -->
          <b-col md="12">
            <b-form-group :label="$t('Reference')">
              <b-form-input label="Reference" :placeholder="$t('Reference')" v-model="Filter_Ref"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- users -->
          <b-col md="12">
            <b-form-group :label="$t('user')">
              <v-select
                v-model="Filter_user"
                :reduce="label => label.value"
                :placeholder="$t('Choose_User')"
                :options="users.map(user => ({label: `${user.firstname} ${user.lastname}`, value: user.id}))"
              />
            </b-form-group>
          </b-col>

          <!-- Customer  -->
          <b-col md="12">
            <b-form-group :label="$t('Customer')">
              <v-select
                :reduce="label => label.value"
                :placeholder="$t('Choose_Customer')"
                v-model="Filter_Client"
                :options="customers.map(customers => ({label: customers.name, value: customers.id}))"
              />
            </b-form-group>
          </b-col>

           <!-- warehouse -->
          <b-col md="12">
            <b-form-group :label="$t('warehouse')">
              <v-select
                v-model="Filter_warehouse"
                :reduce="label => label.value"
                :placeholder="$t('Choose_Warehouse')"
                :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))"
              />
            </b-form-group>
          </b-col>

          <!-- Status  -->
          <b-col md="12">
            <b-form-group :label="$t('Status')">
              <select v-model="Filter_status" type="text" class="form-control">
                <option value selected>All</option>
                <option value="completed">Completed</option>
                <option value="pending">Pending</option>
                <option value="ordered">Ordered</option>
              </select>
            </b-form-group>
          </b-col>

          <!-- Payment Status  -->
          <b-col md="12">
            <b-form-group :label="$t('PaymentStatus')">
              <select v-model="Filter_Payment" type="text" class="form-control">
                <option value selected>All</option>
                <option value="paid">Paid</option>
                <option value="partial">partial</option>
                <option value="unpaid">UnPaid</option>
              </select>
            </b-form-group>
          </b-col>

          <b-col md="6" sm="12">
            <b-button @click="Get_Sales(serverParams.page)" variant="primary ripple m-1" size="sm" block>
              <i class="i-Filter-2"></i>
              {{ $t("Filter") }}
            </b-button>
          </b-col>
          <b-col md="6" sm="12">
            <b-button @click="Reset_Filter()" variant="danger ripple m-1" size="sm" block>
              <i class="i-Power-2"></i>
              {{ $t("Reset") }}
            </b-button>
          </b-col>
        </b-row>
      </div>
    </b-sidebar>
  </div>
  <!-- </div> -->
</template>

<script>
import NProgress from "nprogress";
import jsPDF from "jspdf";
import "jspdf-autotable";
import DateRangePicker from 'vue2-daterange-picker'
import 'vue2-daterange-picker/dist/vue2-daterange-picker.css'

export default {
  metaInfo: {
    title: "Report Sales"
  },
  components: { DateRangePicker },

  data() {
    const today = new Date(); 
    today.setHours(0, 0, 0, 0); // Set the time to midnight
    const defaultEndDate = new Date(today); // Create a copy for the end date
    defaultEndDate.setHours(23, 59, 59, 999);

    return {
      singleDatePicker: false, // set to true if you want to select a single date
      timePicker: true,
      dateFormat: 'yyyy-MM-dd HH:mm:ss',
      timePicker24Hour: true,
      showWeekNumbers: false,
      showDropdowns: true,
      dateRange: [today, defaultEndDate], 
      isLoading: true,
      serverParams: {
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
      limit: "10",
      search: "",
      totalRows: "",
      Filter_Client: "",
      Filter_warehouse: "",
      Filter_Ref: "",
      Filter_status: "",
      Filter_Payment: "",
      Filter_user: "",
      customers: [],
      warehouses: [],
      users: [],
      rows: [{
          statut: 'Total',
         
          children: [
             
          ],
      },],
      sales: [],
      today_mode: true,
      from: "",
      to: "",
    };
  },
  filters: {
    dateCell (value) {
      let dt = new Date(value)
        return dt.getDate()
      },
      date (val) {
        if (!val) return '';
        const dateObject = new Date(val);
        const day = String(dateObject.getDate()).padStart(2, '0');
        const month = String(dateObject.getMonth() + 1).padStart(2, '0');
        const year = dateObject.getFullYear();
        return `${day}/${month}/${year}`;
      }
  },
  computed: {
    columns() {
      return [
        {
          label: this.$t("Date & Time"), // Update the label
          field: "date",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Customer"),
          field: "client_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("User"),
          field: "user_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Status"),
          field: "statut",
          html: true,
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          type: "decimal",
          headerField: this.sumCount,
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Paid"),
          field: "paid_amount",
          type: "decimal",
          headerField: this.sumCount2,
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Due"),
          field: "due",
          type: "decimal",
          headerField: this.sumCount3,
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("PaymentStatus"),
          field: "payment_status",
          html: true,
          tdClass: "text-left",
          thClass: "text-left"
        }
      ];
    },
  },

  beforeMount() {
    const today = new Date();
    const startOfDay = new Date(today); // Clone the date
    startOfDay.setHours(0, 0, 0, 0); // Set to the beginning of the day
    const endOfDay = new Date(today); // Clone the date
    endOfDay.setHours(23, 59, 59, 999); // Set to the end of the day

    this.dateRange = {
      startDate: startOfDay,
      endDate: endOfDay,
    };    
  },

  methods: {
    formatDateAndTime(date, time) {
      // Use any date/time manipulation library of your choice
      const concatenatedDateTime = `${date} ${time}`;
      const formattedDateTime = window.moment(concatenatedDateTime).format("DD/MM/YYYY HH:mm:ss");
      return formattedDateTime;
    },

    get_data_loaded() {
      var self = this;
      if (self.today_mode) {
        const today = new Date();
        const startOfDay = new Date(today); // Clone the date
        startOfDay.setHours(0, 0, 0, 0); // Set to the beginning of the day
        const endOfDay = new Date(today); // Clone the date
        endOfDay.setHours(23, 59, 59, 999);

        self.startDate = this.formatDateTime(startOfDay); // Set startDate to the current date
        self.endDate = this.formatDateTime(endOfDay); // Set endDate to the current date

        self.dateRange = { // Set the date range object with the current date range
          startDate: startOfDay,
          endDate: endOfDay,
        };
      }
      this.Get_Sales(1);
    },

    formatDateTime(dateTime) {
      if (!dateTime) return '';
      const dateObject = new Date(dateTime);
      const day = String(dateObject.getDate()).padStart(2, '0');
      const month = String(dateObject.getMonth() + 1).padStart(2, '0');
      const year = dateObject.getFullYear();
      const hours = String(dateObject.getHours()).padStart(2, '0');
      const minutes = String(dateObject.getMinutes()).padStart(2, '0');
      const seconds = String(dateObject.getSeconds()).padStart(2, '0');
      return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
    },

    sumCount(rowObj) {
     
    	let sum = 0;
      for (let i = 0; i < rowObj.children.length; i++) {
        sum += rowObj.children[i].GrandTotal;
      }
      return sum;
    },
    sumCount2(rowObj) {
     
    	let sum = 0;
      for (let i = 0; i < rowObj.children.length; i++) {
        sum += rowObj.children[i].paid_amount;
      }
      return sum;
    },
    sumCount3(rowObj) {
     
    	let sum = 0;
      for (let i = 0; i < rowObj.children.length; i++) {
        sum += rowObj.children[i].due;
      }
      return sum;
    },

    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Sales(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Sales(1);
      }
    },

    //---- Event on Sort Change

    onSortChange(params) {
      let field = "";
      if (params[0].field == "client_name") {
        field = "client_id";
      } else {
        field = params[0].field;
      }
      this.updateParams({
        sort: {
          type: params[0].type,
          field: field
        }
      });
      this.Get_Sales(this.serverParams.page);
    },

    //---- Event on Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Sales(this.serverParams.page);
    },

    //------ Reset Filter
    Reset_Filter() {
      this.search = "";
      this.Filter_Client = "";
      this.Filter_status = "";
      this.Filter_Payment = "";
      this.Filter_Ref = "";
      this.Filter_user = "";
      this.Filter_warehouse = "";
      this.Get_Sales(this.serverParams.page);
    },

    //------------------------------Formetted Numbers -------------------------\\
    formatNumber(number, dec) {
      const value = (typeof number === "string"
        ? number
        : number.toString()
      ).split(".");
      if (dec <= 0) return value[0];
      let formated = value[1] || "";
      if (formated.length > dec)
        return `${value[0]}.${formated.substr(0, dec)}`;
      while (formated.length < dec) formated += "0";
      return `${value[0]}.${formated}`;
    },

    //------------------------------ Sales PDF ------------------------------\\
    Sales_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Ref", dataKey: "Ref" },
        { title: "Client", dataKey: "client_name" },
        { title: "Warehouse", dataKey: "warehouse_name" },
        { title: "Status", dataKey: "statut" },
        { title: "Total", dataKey: "GrandTotal" },
        { title: "Paid", dataKey: "paid_amount" },
        { title: "Due", dataKey: "due" },
        { title: "Status Payment", dataKey: "payment_status" }
      ];
      pdf.autoTable(columns, self.sales);
      pdf.text("Sales report", 40, 25);
      pdf.save("Sales_report.pdf");
    },

    //----------------------------- Set To Strings -------------------------\\
    setToStrings() {
      // Simply replaces null values with strings=''
      if (this.Filter_Client === null) {
        this.Filter_Client = "";
      }else if (this.Filter_warehouse === null) {
        this.Filter_warehouse = "";
      }
    },

     //----------------------------- Submit Date Picker -------------------\\
    Submit_filter_dateRange() {
      var self = this;
      self.Get_Sales(1);
    },

    convertDate(val) {
      if (!val) return '';
      const dateObject = new Date(val);
      const day = String(dateObject.getDate()).padStart(2, '0');
      const month = String(dateObject.getMonth() + 1).padStart(2, '0');
      const year = dateObject.getFullYear();
      const hours = String(dateObject.getHours()).padStart(2, '0');
      const minutes = String(dateObject.getMinutes()).padStart(2, '0');
      const seconds = String(dateObject.getSeconds()).padStart(2, '0');
      return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
    },

    //----------------------------------------- Get all Sales ------------------------------\\
    Get_Sales(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.setToStrings();

      axios
        .get(
          "/report/sales?page=" +
            page +
            "&Ref=" +
            this.Filter_Ref +
            "&client_id=" +
            this.Filter_Client +
            "&warehouse_id=" +
            this.Filter_warehouse +
            "&user_id=" +
            this.Filter_user +
            "&statut=" +
            this.Filter_status +
            "&payment_statut=" +
            this.Filter_Payment +
            "&SortField=" +
            this.serverParams.sort.field +
            "&SortType=" +
            this.serverParams.sort.type +
            "&search=" +
            this.search +
            "&limit=" +
            this.limit+
            "&from=" +
            this.convertDate(this.dateRange.startDate)+
            "&to=" +
            this.convertDate(this.dateRange.endDate)
        )
        .then(response => {
          this.sales = response.data.sales;
          this.customers = response.data.customers;
          this.warehouses = response.data.warehouses;
          this.users = response.data.users;
          this.totalRows = response.data.totalRows;
          this.rows[0].children = this.sales;

          // Complete the animation of theprogress bar.
          NProgress.done();
          this.isLoading = false;
          this.today_mode = false;
        })
        .catch(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          setTimeout(() => {
            this.isLoading = false;
            this.today_mode = false;
          }, 500);
        });
    }
  },
  //----------------------------- Created function-------------------\\
  created() {
    this.get_data_loaded();
  }
};
</script>