import React, { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { X } from "lucide-react";
import { Input } from "@/components/ui/input";

const Access = () => {
  const [selectedRole, setSelectedRole] = useState("");

  const userRoles = [
    { role: "Super Admin" },
    { role: "Admin" },
    {
      role: "Finance User",
      module: [
        "Reimbursement (Finance)",
        "Tax Slabs (Finance)",
        "Invoicing (Finance)",
        "Accounting (Finance)",
        "Inventory (Finance)",
        "Purchase (Finance)",
        "Expenses (Finance)",
        "Audit (Finance)",
        "Tax Filing (Finance)",
        "Bookkeeping (Finance)",
        "Vendors (Finance)",
      ],
    },
    {
      role: "HR User",
      module: [
        "Employees (Hr)",
        "Payroll (Hr)",
        "Departments (Hr)",
        "Attendance (Hr)",
        "Recruitment (Hr)",
        "Final Settlement (Hr)",
      ],
    },
    {
      role: "Productivity User",
      module: [
        "Timesheet (Productivity)",
        "Project (Productivity)",
        "Documents (Productivity)",
        "Approval (Productivity)",
        "Knowledge (Productivity)",
        "Calendar (Productivity)",
      ],
    },
    {
      role: "General User",
      module: [
        "Reimbursement (Finance)",
        "Tax Slabs (Finance)",
        "Invoicing (Finance)",
        "Accounting (Finance)",
        "Inventory (Finance)",
        "Purchase (Finance)",
        "Expenses (Finance)",
        "Audit (Finance)",
        "Tax Filing (Finance)",
        "Bookkeeping (Finance)",
        "Vendors (Finance)",
        "Employees (Hr)",
        "Payroll (Hr)",
        "Departments (Hr)",
        "Attendance (Hr)",
        "Recruitment (Hr)",
        "Final Settlement (Hr)",
        "Timesheet (Productivity)",
        "Project (Productivity)",
        "Documents (Productivity)",
        "Approval (Productivity)",
        "Knowledge (Productivity)",
        "Calendar (Productivity)",
      ],
    },
  ];

  const [filteredRole, setFilteredRole] = useState([]);

  useEffect(() => {
    const roleData = userRoles.filter((e) => e.role === selectedRole);
    setFilteredRole(roleData);
  }, [selectedRole]);

  console.log(filteredRole);

  const handleRemoveModule = (index) => {
    setFilteredRole((prev) => {
      const updated = [...prev];
      updated[0].module = updated[0].module.filter((_, i) => index !== i);
      return updated;
    });
  };

  const [query, setQuery] = useState("");
  const [filteredData, setFilteredData] = useState([]);
  const [addedApp, setAddedApp] = useState([{}]);
  const [accessType, setAccessType] = useState("");

  const handleSuggestions = (e) => {
    const value = e.target.value;
    setQuery(value);
    console.log(query);

    if (value.length > 0) {
      const results = filteredRole[0].module.filter((item) =>
        item.toLowerCase().includes(value.toLowerCase())
      );
      setFilteredData(results);
    } else {
      setFilteredData([]);
    }
  };

  const handleAddedApps = () => {
    if (!query || !accessType) return;

    const newApp = {
      name: query,
      access: accessType,
    };

    setAddedApp([...addedApp, newApp]);
    setQuery("");
    setAccessType("");
  };

  return (
    <div className="h-[50vh] overflow-y-scroll px-6 bg-white">
      <h1 className="font-semibold text-xl mb-7">Access Control</h1>

      <div className="flex items-center mt-5 gap-4">
        <div className="mt-5 flex-1">
          <Label className="mb-2 block text-gray-700">Select User</Label>
          <Select>
            <SelectTrigger className="w-full">
              <SelectValue placeholder="Select a user to modify" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>User</SelectLabel>
                <SelectItem value="john">John</SelectItem>
                <SelectItem value="sarah">Sarah</SelectItem>
                <SelectItem value="mike">Mike</SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>

        <div className="mt-5 flex-1">
          <Label className="mb-2 block text-gray-700">User Role</Label>
          <Select onValueChange={(e) => setSelectedRole(e)}>
            <SelectTrigger className="w-full">
              <SelectValue placeholder="Select a role" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>User Role</SelectLabel>
                {userRoles.map((e, i) => (
                  <SelectItem key={i} value={e.role}>
                    {e.role}
                  </SelectItem>
                ))}
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>
      </div>
      <div>
        {selectedRole !== "General User" ? (
          filteredRole.length > 0 &&
          filteredRole[0].module && (
            <div className="mt-8 bg-gray-500/5 p-4 rounded-lg border-1">
              <h1 className="font-semibold mb-5">Grant Module Permissions:</h1>

              {filteredRole[0].module?.map((e, i) => (
                <div
                  key={i}
                  className="flex mt-1 items-center justify-between bg-gray-50 rounded-md px-4 py-2 shadow-sm"
                >
                  <div className="font-medium text-gray-700">{e}</div>

                  <div className="flex items-center gap-2 w-60">
                    <Select>
                      <SelectTrigger className="w-full">
                        <SelectValue placeholder="Select Permissions" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectLabel>Permissions</SelectLabel>
                          <SelectItem value="viewOnly">View Only</SelectItem>
                          <SelectItem value="viewAndEdit">
                            View and Edit
                          </SelectItem>
                          <SelectItem value="post">Post</SelectItem>
                          <SelectItem value="delete">Delete</SelectItem>
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    <X
                      onClick={() => handleRemoveModule(i)}
                      className="w-5 h-5 text-gray-500 cursor-pointer hover:text-red-500"
                    />
                  </div>
                </div>
              ))}
            </div>
          )
        ) : (
          <div className="mt-8 bg-gray-500/5 p-4 rounded-lg border-1">
            <h1 className="font-semibold mb-5">Grant Module Permissions:</h1>

            <div className="relative w-full mt-5">
              {/* Search Input */}
              <Label>Search and Add Applications</Label>
              <Input
                type="text"
                placeholder="Search for an application..."
                value={query}
                onChange={(e) => handleSuggestions(e)}
                className="w-full p-2 mt-2 border border-gray-300 rounded-md focus:outline-none"
              />

              <div className="mt-5">
                <Label className={"mb-2"}>Access Type</Label>
                <Select
                  onValueChange={(val) => setAccessType(val)}
                  value={accessType}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Select Permissions" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel>Permissions</SelectLabel>
                      <SelectItem value="view Only">View Only</SelectItem>
                      <SelectItem value="View And Edit">
                        View and Edit
                      </SelectItem>
                      <SelectItem value="Post">Post</SelectItem>
                      <SelectItem value="Delete">Delete</SelectItem>
                    </SelectGroup>
                  </SelectContent>
                </Select>
              </div>
              <div className="mt-2 flex justify-center">
                <Button
                  onClick={handleAddedApps}
                  className="bg-[#7BC9EE] hover:bg-sky-600 my-5  cursor-pointer"
                >
                  Add Application
                </Button>
              </div>
              <div>
                {!addedApp.length
                  ? ""
                  : addedApp.length > 0 &&
                    addedApp.map((e, i) => (
                      <div
                        key={i}
                        className="flex mt-1 items-center justify-between bg-gray-50 rounded-md px-4 py-2 shadow-sm"
                      >
                        <div className="font-medium text-gray-700">
                          {e.name}
                        </div>

                        <div className="flex items-center gap-2 w-60">
                          <Input defaultValue={e.access} readOnly />
                          <X
                            onClick={() =>
                              setAddedApp(
                                addedApp.filter((_, index) => index !== i)
                              )
                            }
                            className="w-5 h-5 text-gray-500 cursor-pointer hover:text-red-500"
                          />
                        </div>
                      </div>
                    ))}
              </div>

              {/* Suggestions */}
              {filteredData.length > 0 && (
                <ul className="absolute left-0 top-15 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-md max-h-40 overflow-y-auto z-10">
                  {filteredData.map((item, index) => (
                    <li
                      key={index}
                      className="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                      onClick={() => {
                        setQuery(item);
                        setFilteredData([]);
                      }}
                    >
                      {item}
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        )}
      </div>

      <div className="mt-2 flex justify-center">
        <Button className="bg-[#7BC9EE] hover:bg-sky-600 my-5  cursor-pointer">
          Update Access
        </Button>
      </div>
    </div>
  );
};

export default Access;
