import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { faTableList } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import {  SquarePen, Trash } from "lucide-react"
import React from 'react'

const EmployeeTable = () => {
  return (
    <div className='mt-10 pb-20 px-5'>
        <h1 className='text-sky-600 text-xl font-bold mb-5'><FontAwesomeIcon icon={faTableList}/> Employee List</h1>
        <hr className="mb-10" />
       <Table>
  <TableHeader>
    <TableRow>
      <TableHead>Photo</TableHead>
      <TableHead>Emp.No.</TableHead>
      <TableHead>Name</TableHead>
      <TableHead>Designation</TableHead>
      <TableHead>Department</TableHead>
      <TableHead>Status</TableHead>
      <TableHead>Data of Joining</TableHead>
      <TableHead>Basic Salary</TableHead>
      <TableHead>Actions</TableHead>
    </TableRow>
  </TableHeader>
  <TableBody>
    <TableRow>
      <TableCell className="font-medium">SA</TableCell>
      <TableCell>101</TableCell>
      <TableCell>John Doe</TableCell>
      <TableCell>Assistant</TableCell>
      <TableCell>Accounts</TableCell>
      <TableCell>Active</TableCell>
      <TableCell>September 07, 2025</TableCell>
      <TableCell>40,000</TableCell>
     <TableCell className="flex gap-3 items-center">
  <Trash className="w-6 h-6 text-red-500" />
  <SquarePen className="w-6 h-6 text-blue-500" />
</TableCell>

    </TableRow>
  </TableBody>
</Table>
    </div>
  )
}

export default EmployeeTable