import SearchInput from "@/components/Dashboard/Employees/SearchInput";
import { Button } from "@/components/ui/button";
import { faUser } from "@fortawesome/free-regular-svg-icons";
import { faChartSimple, faUserPlus } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import Link from "next/link";
import React from "react";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import { StatusFilter } from "@/components/Dashboard/Employees/StatusFilter";
import EmployeeTable from "@/components/Dashboard/Employees/EmployeeTable";

const page = () => {
  return (
    <div className="max-w-7xl px-4 mx-auto bg-white">
      <div className="flex items-center justify-between">
        <div className="mt-15 ">
          <h1 className="text-3xl font-bold flex text-sky-800 items-center gap-2 mb-2">
            <FontAwesomeIcon icon={faUser} size={5} /> Employees Management
          </h1>
          <p className="text-muted-foreground text-sm pl-11">
            Manage and maintain all employee records and employment statuses.
          </p>
        </div>
      </div>
      <div className="flex justify-between px-5 items-center mt-12">
        <div className="transition-transform duration-300 ease-in-out hover:scale-105 hover:-translate-y-2 cursor-pointer flex w-[224px] h-[120px] border-2 font-bold bg-[#498FF7] text-white shadow-2xl">
          <div className="bg-[#4F46E5] w-[6px]"></div>

          <div className="flex flex-col flex-1 justify-center items-center">
            <span className="text-4xl">0</span>
            <span className="text-[18px]">Total Employees</span>
          </div>
        </div>

        <div className="transition-transform duration-300 ease-in-out hover:scale-105 hover:-translate-y-2 cursor-pointer flex w-[224px] h-[120px] gap-1 font-bold bg-[#41D878]  text-white shadow-2xl border-2  ">
          <div className="bg-[#4F46E5] w-[6px]"></div>

          <div className="flex flex-col flex-1 justify-center items-center">
            <span className="text-4xl">0</span>
            <span className="text-[18px]">Active Employees</span>
          </div>
        </div>
        <div className="hover:stransition-transform duration-300 ease-in-out hover:scale-105 hover:-translate-y-2 cursor-pointer flex  w-[224px] h-[120px] gap-1 font-bold bg-[#F6A510]  text-white shadow-2xl border-2">
          <div className="bg-[#4F46E5] w-[6px]"></div>

          <div className="flex flex-col flex-1 justify-center items-center">
            <span className="text-4xl">0</span>
            <span className="text-[18px]">On Leave</span>
          </div>
        </div>
        <div className="transition-transform duration-300 ease-in-out hover:scale-105 hover:-translate-y-2 cursor-pointer flex w-[224px] h-[120px] gap-1 font-bold bg-[#9267F7]  text-white shadow-2xl border-2">
          <div className="bg-[#4F46E5] w-[6px]"></div>

          <div className="flex flex-col flex-1 justify-center items-center">
            <span className="text-4xl">0</span>
            <span className="text-[18px]">Resigned</span>
          </div>
        </div>
        <div className="transition-transform duration-300 ease-in-out hover:scale-105 hover:-translate-y-2 cursor-pointer flex  w-[224px] h-[120px] gap-1 font-bold bg-[#E43232] text-white shadow-2xl border-2">
          <div className="bg-[#4F46E5] w-[6px]"></div>

          <div className="flex flex-col flex-1 justify-center items-center">
            <span className="text-4xl">0</span>
            <span className="text-[18px]">Terminated</span>
          </div>
        </div>
      </div>
      <div className="flex items-end gap-4 w-full mt-10 px-5 border-1 py-4 shadow-[0_4px_10px_rgba(0,0,0,0.1)] border-gray-100 rounded-lg">
        <div className="flex-1 min-w-[300px]">
          <SearchInput />
        </div>

        <div>
          <Label className="mb-2 block text-muted-foreground text-xs">
            Filter by Department
          </Label>
          <Select>
            <SelectTrigger className="w-[180px] cursor-pointer">
              <SelectValue placeholder="Department" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="hr">HR</SelectItem>
              <SelectItem value="finance">Finance</SelectItem>
              <SelectItem value="it">IT</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div>
          <StatusFilter />
        </div>
        <div className="flex gap-3">
          <Link href={"/"}>
            <Button className="bg-[#10B981] cursor-pointer hover:bg-green-600 p-5">
              <FontAwesomeIcon icon={faUserPlus} /> Add New
            </Button>
          </Link>
          <Link href={"/"}>
            <Button className="bg-[#498FF7] cursor-pointer hover:bg-sky-600 p-5">
              <FontAwesomeIcon icon={faChartSimple} /> Reports
            </Button>
          </Link>
        </div>
      </div>
      <div>
<EmployeeTable/>
      </div>
    </div>
  );
};

export default page;
