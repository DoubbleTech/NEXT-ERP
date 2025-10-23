// import {
//   faAddressBook,
//   faBuilding,
//   faCalendar,
//   faCalendarAlt,
//   faCalendarCheck,
//   faCheckCircle,
//   faClock,
//   faFileAlt,
//   faFolder,
//   faTruck,
// } from "@fortawesome/free-regular-svg-icons";
import { getUser } from "@/actions/get-user";
import {
  // faBook,
  // faBoxes,
  faCalculator,
  faFileInvoice,
  // faFileInvoiceDollar,
  // faHandHoldingUsd,

  // faMoneyBillWave,
  // faMoneyCheckAlt,
  // faPercent,
  // faSearch,
  faShoppingCart,
  faTasks,
  // faUserPlus,
  // faUsers,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import Link from "next/link";
import React from "react";

export const applications = [
  // {
  //   icon: faUsers,
  //   class: "#3b82f6",
  //   name: "Employees",
  // },
  // {
  //   icon: faMoneyBillWave,
  //   class: "#22c55e",

  //   name: "Payroll",
  // },
  // {
  //   icon: faMoneyCheckAlt,
  //   class: "#f97316",

  //   name: "Reimbursement",
  // },
  // {
  //   icon: faBuilding,
  //   class: "#8b5cf6",

  //   name: "Departments",
  // },
  // {
  //   icon: faPercent,
  //   class: "#ef4444",

  //   name: "Tax Slabs",
  // },
  {
    icon: faFileInvoice,
    class: "#06b6d4",

    name: "Sales",
  },
  {
    icon: faCalculator,
    class: "#eab308",

    name: "Accounting",
  },
  // {
  //   icon: faClock,
  //   class: "#a855f7",

  //   name: "Timesheet",
  // },
  {
    icon: faTasks,
    class: "#10b981",

    name: "Project",
  },
  // {
  //   icon: faBoxes,
  //   class: "#f43f5e",

  //   name: "Inventory",
  // },
  {
    icon: faShoppingCart,
    class: "#f59e0b",

    name: "Purshase",
  },

  // {
  //   icon: faFolder,
  //   class: "#60a5fa",

  //   name: "Documents",
  // },
  // {
  //   icon: faCalendarCheck,
  //   class: "#14b8a6",

  //   name: "Attendance",
  // },
  // {
  //   icon: faFileInvoiceDollar,
  //   class: "#fb923c",

  //   name: "Expenses",
  // },
  // {
  //   icon: faCheckCircle,
  //   class: "#84cc16",

  //   name: "Approval",
  // },
  // {
  //   icon: faUserPlus,
  //   class: "#ec4899",

  //   name: "Recruitment",
  // },
  // {
  //   icon: faHandHoldingUsd,
  //   class: "#78350f",

  //   name: "Settlement",
  // },
  // {
  //   icon: faSearch,
  //   class: "#4b5563",

  //   name: "Audit",
  // },
  // {
  //   icon: faFileAlt,
  //   class: "#6d28d9",

  //   name: "Tax Filing",
  // },
  // {
  //   icon: faBook,
  //   class: "#9333ea",

  //   name: "Knowledge",
  // },
  // {
  //   icon: faCalculator,
  //   class: "#f59e0b",

  //   name: "Bookkepping",
  // },
  // {
  //   icon: faTruck,
  //   class: "#334155",
  //   name: "Vendors",
  // },
  // {
  //   icon: faCalendarAlt,
  //   class: "#f472b6",
  //   name: "Calendar",
  // },
  // {
  //   icon: faAddressBook,
  //   class: "#1d4ed8",
  //   name: "Contacts",
  // },
];

const Page = async () => {
  const SuperAdminUser = await getUser();
  
  const { name } = SuperAdminUser || {};

  return (
    <div className=" w-full bg-gray-200">
      <div className="flex capitalize justify-center items-center mt-18 font-semibold text-3xl">
        <h1>Greetings, {name}!</h1>
      </div>
      {/* Grid Section */}

      <div className="max-w-5xl mx-auto grid  grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4  gap-10 py-20 ">
        {applications.map((e, i) => (
          <Link key={i} href={`/dashboard/${e.name.toLowerCase()}`}>
            <div className="bg-gray-100 flex flex-col p-3 items-center w-60 rounded-lg shadow-lg  cursor-pointer hover:scale-105 transition-all duration-400 hover:shadow-2xl">
              <div className="">
                <FontAwesomeIcon
                  icon={e.icon}
                  style={{ fontSize: "2.5rem", color: e.class }}
                />
              </div>
              <div className="mt-3 font-bold text-[18px]">
                <span>{e.name}</span>
              </div>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
};

export default Page;
