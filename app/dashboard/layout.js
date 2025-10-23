import { getUser } from "@/actions/get-user";
import Navbar from "@/components/Dashboard/Navbar";
import React from "react";

const layout = async({ children }) => {
const SuperAdminUser =   await getUser()


const {name,email} = SuperAdminUser  || {}


  return (
    <div className="flex flex-col h-screen bg-gray-200">
   <div >
    <Navbar name={name} email={email}/>
   </div>
   <div>
    {children}
   </div>
    </div>
  );
};

export default layout;
