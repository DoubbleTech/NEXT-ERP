import React from 'react'
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
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

const Users = () => {
  return (
     <div className="h-[50vh] overflow-y-scroll  px-6">
      <h1 className=" font-semibold text-xl mb-7">
        User Management
      </h1>

      <div>
        <div className='flex items-center mt-5 gap-4'>
          <div className='mt-5 flex-1'>
            <Label className="mb-2 block">Select an Employee</Label>
          <Select>
            <SelectTrigger className="w-full">
              <SelectValue placeholder="Select an existing employee" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Employees</SelectLabel>
                <SelectItem value="apple">John</SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
          </div>
          <div className="mt-5 flex-1">
            <Label className="mb-2 block">Email</Label>
            <Input className="w-full" placeholder="Enter user's email" type="email" />
          </div>
        </div>
        <div className="flex items-center mt-5 gap-4">
          <div className="mt-5 flex-1">
            <Label className="mb-2 block">Password</Label>
            <Input className="w-full" placeholder="Create a password" type="password" />
          </div>
          <div className="mt-5 flex-1">
             <Label className="mb-2 block">User Role</Label>
          <Select>
            <SelectTrigger className="w-full">
              <SelectValue placeholder="Select a role" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Roles</SelectLabel>
                <SelectItem value="admin">Admin</SelectItem>
                <SelectItem value="user">User</SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
          </div>
        </div>
        
        
        <Button className="bg-[#7BC9EE] hover:bg-sky-600 my-10 cursor-pointer">
          Create User
        </Button>
      </div>
    </div>
  )
}

export default Users